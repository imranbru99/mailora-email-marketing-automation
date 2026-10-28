<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\EmailEvent;
use App\Models\FeedbackEndpoint;
use App\Models\FeedbackEvent;
use App\Models\MessageLog;
use App\Models\Suppression;
use Illuminate\Support\Facades\DB;

class FeedbackProcessor
{
    public function process(int $eventId): void
    {
        if (! app(ModuleRegistry::class)->enabled('feedback')) {
            return;
        }
        DB::transaction(function () use ($eventId) {
            Business::lockForUpdate()->findOrFail(app(TenantContext::class)->businessId);
            $pending = FeedbackEvent::find($eventId);
            if (! $pending || $pending->status === 'processed') {
                return;
            }
            $message = MessageLog::lockForUpdate()->find($pending->message_log_id);
            $event = FeedbackEvent::lockForUpdate()->findOrFail($eventId);
            $endpoint = FeedbackEndpoint::withoutGlobalScope('branch')->find($event->feedback_endpoint_id);
            if (! $endpoint?->active || ! $message || $event->status === 'processed') {
                return;
            }
            $campaign = Campaign::lockForUpdate()->find($message->campaign_id);
            if (! $campaign || ! $message->recipient_email) {
                $event->update(['status' => 'ignored', 'processed_at' => now()]);

                return;
            }
            // A signed receipt also resolves an interrupted SMTP attempt without resending it.
            if (in_array($message->status, ['dispatching', 'uncertain'], true)) {
                $message->update(['status' => 'sent', 'sent_at' => $message->sent_at ?? now()]);
                $campaign->increment('sent_count');
            }
            $changes = [];
            if ($event->type === 'delivered' && ! $message->delivered_at) {
                $changes['delivered_at'] = now();
                $campaign->increment('delivered_count');
            }
            if (in_array($event->type, ['hard_bounce', 'soft_bounce'], true) && ! $message->bounced_at) {
                $changes['bounced_at'] = now();
                $campaign->increment('bounce_count');
            }
            if ($event->type === 'complaint' && ! $message->complained_at) {
                $changes['complained_at'] = now();
                $campaign->increment('complaint_count');
            }
            $severity = ['delivered' => 1, 'soft_bounce' => 2, 'hard_bounce' => 3, 'complaint' => 4];
            if (($severity[$event->type] ?? 0) > ($severity[$message->delivery_status] ?? 0)) {
                $changes['delivery_status'] = $event->type;
            }
            $message->update($changes);
            if (in_array($event->type, ['hard_bounce', 'complaint'], true)) {
                Suppression::withoutGlobalScope('branch')->firstOrCreate(['email' => $message->recipient_email], ['reason' => $event->type]);
                $contact = Contact::find($message->contact_id);
                if ($contact && $contact->email === $message->recipient_email) {
                    $contact->update(['status' => $event->type === 'complaint' ? 'unsubscribed' : 'bounced']);
                }
            }
            EmailEvent::create(['branch_id' => $message->branch_id, 'message_log_id' => $message->id, 'type' => $event->type, 'is_bot' => false]);
            $event->update(['status' => 'processed', 'processed_at' => now()]);
            Audit::record('feedback.'.$event->type, $message);
            app(CampaignService::class)->finish($campaign);
        }, 3);
    }
}
