<?php

namespace App\Http\Controllers;

use App\Jobs\SendOptInConfirmation;
use App\Models\Campaign;
use App\Models\ConsentRecord;
use App\Models\Contact;
use App\Models\EmailEvent;
use App\Models\MessageLog;
use App\Models\SignupForm;
use App\Services\AutomationEngine;
use App\Services\ContactService;
use App\Services\ModuleRegistry;
use App\Services\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PublicController extends Controller
{
    private function context(string $model, string $field, string $value): mixed
    {
        $record = $model::withoutGlobalScopes()->where($field, $value)->firstOrFail();
        if (method_exists($record, 'trashed') && $record->trashed()) {
            abort(404);
        }
        app(TenantContext::class)->set($record->business_id, $record->branch_id);

        return $record;
    }

    public function form(string $id): View
    {
        $form = $this->context(SignupForm::class, 'public_id', $id);
        abort_unless($form->published && app(ModuleRegistry::class)->enabled('forms'), 404);

        return view('public.form', compact('form'));
    }

    public function subscribe(Request $request, string $id): RedirectResponse
    {
        $form = $this->context(SignupForm::class, 'public_id', $id);
        abort_unless($form->published && app(ModuleRegistry::class)->enabled('forms'), 404);
        $data = $request->validate(['first_name' => 'required|string|max:100', 'email' => 'required|email:rfc|max:254', 'consent' => 'accepted', 'website' => 'nullable|max:0']);
        $email = mb_strtolower(trim($data['email']));
        DB::transaction(function () use ($data, $email, $form) {
            $contact = Contact::withoutGlobalScope('branch')->where('email', $email)->first();
            if ($contact) {
                return;
            } // Existing opt-outs and pending confirmations remain unchanged.
            $contact = Contact::create(['branch_id' => $form->branch_id, 'first_name' => $data['first_name'], 'email' => $email, 'source' => 'form', 'status' => $form->double_opt_in ? 'pending' : 'subscribed', 'consented_at' => $form->double_opt_in ? null : now(), 'legal_basis' => 'consent']);
            ConsentRecord::create(['contact_id' => $contact->id, 'action' => 'requested', 'source' => 'form:'.$form->public_id, 'proof' => 'Explicit checkbox accepted']);
            if ($form->audience_list_id) {
                $contact->lists()->syncWithoutDetaching([$form->audience_list_id]);
            }
            if ($form->double_opt_in) {
                SendOptInConfirmation::dispatch($contact->business_id, $contact->branch_id, $contact->id)->afterCommit();
            } else {
                app(AutomationEngine::class)->enroll($contact, 'form.submitted');
            }
        });

        return back()->with('success', $form->double_opt_in ? 'Thank you. If eligible, a confirmation link has been sent. Sandbox confirmations are recorded in the mail log.' : 'Thank you. Your request has been received.');
    }

    public function confirm(string $id): View
    {
        $contact = $this->context(Contact::class, 'public_id', $id);
        abort_unless(app(ModuleRegistry::class)->enabled('forms'), 404);
        if ($contact->status === 'pending') {
            $contact->update(['status' => 'subscribed', 'consented_at' => now()]);
            ConsentRecord::create(['contact_id' => $contact->id, 'action' => 'confirmed', 'source' => 'double opt-in']);
            app(AutomationEngine::class)->enroll($contact, 'form.submitted');
        }

        return view('public.message', ['title' => 'Subscription confirmed', 'message' => 'Your email preferences have been saved. You can unsubscribe at any time.']);
    }

    public function unsubscribe(string $token): View
    {
        $contact = $this->context(Contact::class, 'unsubscribe_token', $token);
        app(ContactService::class)->unsubscribe($contact);

        return view('public.message', ['title' => 'You’re unsubscribed', 'message' => 'You will no longer receive marketing emails from this business.']);
    }

    public function open(Request $request, string $id): Response
    {
        $message = $this->context(MessageLog::class, 'public_id', $id);
        if (app(ModuleRegistry::class)->enabled('tracking') && Campaign::find($message->campaign_id)?->tracking && $message->sent_at) {
            DB::transaction(function () use ($message, $request) {
                $bot = (bool) preg_match('/bot|crawler|proxy/i', $request->userAgent() ?? '');
                EmailEvent::create(['message_log_id' => $message->id, 'type' => 'open', 'is_bot' => $bot]);
                if (! $bot && MessageLog::whereKey($message->id)->whereNull('opened_at')->update(['opened_at' => now()])) {
                    Campaign::whereKey($message->campaign_id)->increment('open_count');
                }
            });
        }

        return response(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'), 200, ['Content-Type' => 'image/gif', 'Cache-Control' => 'no-store, private']);
    }

    public function click(Request $request, string $token): RedirectResponse
    {
        $link = DB::table('tracking_links')->where('token', $token)->first();
        abort_unless($link !== null, 404);
        $message = MessageLog::withoutGlobalScopes()->findOrFail($link->message_log_id);
        app(TenantContext::class)->set($message->business_id, $message->branch_id);
        if (app(ModuleRegistry::class)->enabled('tracking') && Campaign::find($message->campaign_id)?->tracking) {
            DB::transaction(function () use ($message, $request) {
                $bot = (bool) preg_match('/bot|crawler|proxy/i', $request->userAgent() ?? '');
                EmailEvent::create(['message_log_id' => $message->id, 'type' => 'click', 'is_bot' => $bot]);
                if (! $bot && MessageLog::whereKey($message->id)->whereNull('clicked_at')->update(['clicked_at' => now()])) {
                    Campaign::whereKey($message->campaign_id)->increment('click_count');
                }
            });
        }
        abort_unless(in_array(parse_url($link->destination, PHP_URL_SCHEME), ['http', 'https'], true), 404);

        return redirect()->away($link->destination);
    }

    public function preferences(string $token): View
    {
        $contact = $this->context(Contact::class, 'unsubscribe_token', $token);
        abort_unless(app(ModuleRegistry::class)->enabled('preferences'), 404);

        return view('public.preferences', compact('contact'));
    }

    public function savePreferences(Request $request, string $token): RedirectResponse
    {
        $contact = $this->context(Contact::class, 'unsubscribe_token', $token);
        abort_unless(app(ModuleRegistry::class)->enabled('preferences'), 404);
        $data = $request->validate(['locale' => 'required|in:en,bn,ar', 'frequency' => 'required|in:daily,weekly,monthly', 'pause' => 'nullable|integer|in:0,7,30,90']);
        $contact->update(['locale' => $data['locale'], 'frequency' => $data['frequency'], 'paused_until' => ($data['pause'] ?? 0) > 0 ? now()->addDays((int) $data['pause']) : null]);
        ConsentRecord::create(['contact_id' => $contact->id, 'action' => 'preferences_updated', 'source' => 'preference center']);

        return back()->with('success','Your preferences have been saved.');
    }
}
