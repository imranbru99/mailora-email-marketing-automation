<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\Suppression;
use App\Services\ModuleRegistry;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class SendOptInConfirmation implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $businessId, public ?int $branchId, public int $contactId) {}

    public function handle(): void
    {
        app(TenantContext::class)->run($this->businessId, $this->branchId, function () {
            if (! app(ModuleRegistry::class)->enabled('forms')) {
                return;
            }
            $contact = Contact::find($this->contactId);
            if (! $contact || $contact->status !== 'pending' || Suppression::where('email', $contact->email)->exists()) {
                return;
            }
            if (! config('platform.sandbox') && (config('mail.default') !== 'smtp' || ! str_starts_with(config('app.url'), 'https://'))) {
                throw new \RuntimeException('Live confirmation mail requires SMTP and an HTTPS application URL.');
            }
            $url = URL::temporarySignedRoute('confirm', now()->addDays(2), ['id' => $contact->public_id]);
            Mail::mailer(config('platform.sandbox') ? 'log' : 'smtp')->raw("Confirm your subscription: $url", fn ($message) => $message->to($contact->email)->subject('Confirm your subscription'));
        });
    }
}
