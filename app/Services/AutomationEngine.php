<?php

namespace App\Services;

use App\Jobs\SendMessage;
use App\Models\Automation;
use App\Models\AutomationRun;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\MessageLog;
use App\Models\SendingIdentity;
use App\Models\Task;
use App\Models\Template;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AutomationEngine
{
    /**
     * @param  list<array{type?:string,value?:string,identity_id?:int,field?:string,on_true?:int,on_false?:int,email?:array{subject:string,html:string}}>  $steps
     */
    public function validate(array $steps): void
    {
        if (! $steps || count($steps) > 100) {
            throw ValidationException::withMessages(['steps' => 'Use between 1 and 100 steps.']);
        }
        foreach ($steps as $step) {
            if (! in_array($step['type'] ?? '', ['wait', 'tag', 'score', 'task', 'unsubscribe', 'email', 'remove_tag', 'set_field', 'condition'], true)) {
                throw ValidationException::withMessages(['steps' => 'Unsupported action.']);
            }
            if ($step['type'] === 'email' && (! isset($step['email']) && ! Template::whereKey($step['value'] ?? 0)->exists() || ! SendingIdentity::whereKey($step['identity_id'] ?? 0)->exists())) {
                throw ValidationException::withMessages(['steps' => 'Select a template and sending identity for each email step.']);
            }
            if ($step['type'] === 'wait' && (! is_numeric($step['value'] ?? null) || (int) $step['value'] < 1 || (int) $step['value'] > 525600)) {
                throw ValidationException::withMessages(['steps' => 'Wait must be 1–525600 minutes.']);
            }
        }
    }

    public function enroll(Contact $contact, string $event): void
    {
        if (! app(ModuleRegistry::class)->enabled('automation')) {
            return;
        }
        foreach (Automation::where('status', 'live')->where('trigger', $event)->where('branch_id', $contact->branch_id)->get() as $flow) {
            $this->validate($flow->steps);
            AutomationRun::firstOrCreate(['automation_id' => $flow->id, 'contact_id' => $contact->id, 'version' => $flow->version], ['steps' => $flow->steps, 'branch_id' => $contact->branch_id, 'next_run_at' => now()]);
        }
    }

    public function tick(int $runId): void
    {
        if (! app(ModuleRegistry::class)->enabled('automation')) {
            return;
        }
        DB::transaction(function () use ($runId) {
            $run = AutomationRun::lockForUpdate()->find($runId);
            if (! $run || $run->status !== 'running' || $run->next_run_at?->isFuture()) {
                return;
            }
            $flow = Automation::find($run->automation_id);
            if (! $flow || $flow->status !== 'live') {
                return;
            }
            $contact = Contact::find($run->contact_id);
            if (! $contact || $contact->status === 'unsubscribed') {
                $run->update(['status' => 'exited']);

                return;
            }
            $step = $run->steps[$run->position] ?? null;
            if (! $step) {
                $run->update(['status' => 'completed']);

                return;
            }
            if (count($run->history ?? []) >= 1000) {
                $run->update(['status' => 'capped']);

                return;
            }
            $next = now();
            $position = $run->position + 1;
            $value = $step['value'] ?? '';
            switch ($step['type']) {
                case 'wait': $next = now()->addMinutes((int) $value);
                    break;
                case 'tag': $contact->update(['tags' => array_values(array_unique([...($contact->tags ?? []), (string) $value]))]);
                    break;
                case 'score': $contact->increment('score', max(-100, min(100, (int) $value)));
                    break;
                case 'task':
                    if (! app(ModuleRegistry::class)->enabled('tasks')) {
                        return;
                    }
                    Task::create(['branch_id' => $contact->branch_id, 'name' => (string) $value, 'description' => 'Follow up with '.$contact->email]);
                    break;
                case 'remove_tag': $contact->update(['tags' => array_values(array_diff($contact->tags ?? [], [(string) $value]))]);
                    break;
                case 'set_field':
                    if (in_array($step['field'] ?? '', ['company', 'locale', 'timezone'], true)) {
                        $contact->update([$step['field'] => $value]);
                    }
                    break;
                case 'condition':
                    $matches = in_array((string) $value, $contact->tags ?? [], true);
                    $position = (int) ($matches ? ($step['on_true'] ?? $position) : ($step['on_false'] ?? $position));
                    if ($position < 0 || $position > count($run->steps)) {
                        throw new \RuntimeException('Invalid condition destination.');
                    }
                    break;
                case 'email':
                    if (! app(ModuleRegistry::class)->enabled('campaigns')) {
                        return;
                    }
                    if (! isset($step['email'])) {
                        $run->update(['status' => 'needs_republish']);

                        return;
                    }
                    $sender = SendingIdentity::findOrFail((int) $step['identity_id']);
                    $campaign = Campaign::create(['branch_id' => $contact->branch_id, 'name' => $flow->name.' · step '.($run->position + 1), 'subject' => $step['email']['subject'], 'html' => $step['email']['html'], 'sending_identity_id' => $sender->id, 'approved_at' => now(), 'status' => 'sending', 'automation_run_id' => $run->id]);
                    $message = MessageLog::create(['branch_id' => $contact->branch_id, 'campaign_id' => $campaign->id, 'contact_id' => $contact->id]);
                    SendMessage::dispatch($run->business_id, $run->branch_id, $message->id)->afterCommit();
                    break;
                case 'unsubscribe': app(ContactService::class)->unsubscribe($contact, 'automation');
                    break;
            }
            $run->update(['position' => $position, 'next_run_at' => $next, 'history' => [...($run->history ?? []), ['step' => $run->position, 'type' => $step['type'], 'at' => now()->toIso8601String()]]]);
        }, 3);
    }
}
