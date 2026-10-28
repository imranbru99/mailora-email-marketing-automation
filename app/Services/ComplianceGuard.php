<?php

namespace App\Services;

use App\Models\Automation;
use App\Models\AutomationRun;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Suppression;

class ComplianceGuard
{
    /**
     * @return list<string>
     */
    public function campaignErrors(Campaign $campaign): array
    {
        $errors = [];
        if ($campaign->automation_run_id) {
            $run = AutomationRun::find($campaign->automation_run_id);
            $flow = $run ? Automation::find($run->automation_id) : null;
            if (! app(ModuleRegistry::class)->enabled('automation') || ! $flow || $flow->status !== 'live') {
                $errors[] = 'Automation is paused or disabled.';
            }
        }
        if (! app(ModuleRegistry::class)->enabled('campaigns')) {
            $errors[] = 'Campaign module is disabled.';
        }
        if (! $campaign->identity || ! $campaign->identity->verified_at) {
            $errors[] = 'A verified sending identity is required.';
        }
        if (! $campaign->identity?->postal_address) {
            $errors[] = 'A postal address is required.';
        }
        if (! str_contains($campaign->html, '{{unsubscribe_url}}')) {
            $errors[] = 'Include the {{unsubscribe_url}} merge tag.';
        }
        if (! trim($campaign->subject)) {
            $errors[] = 'A subject is required.';
        }
        if (! $campaign->approved_at) {
            $errors[] = 'Campaign approval is required.';
        }
        if (! config('platform.sandbox')) {
            $sender = $campaign->identity;
            if (! $sender?->dns_verified_at || $sender->dns_verified_at->lt(now()->subDays(30))) {
                $errors[] = 'Domain ownership, SPF, and DMARC must be verified within 30 days.';
            }
            if (config('mail.default') !== 'smtp') {
                $errors[] = 'Configure the SMTP mailer before enabling live delivery.';
            }
            if (! str_starts_with(config('app.url'), 'https://')) {
                $errors[] = 'Live campaigns require an HTTPS application URL.';
            }
        }

        return $errors;
    }

    public function contactError(Contact $contact): ?string
    {
        if ($contact->paused_until?->isFuture()) {
            return 'Contact has paused marketing emails.';
        }
        if ($contact->status !== 'subscribed') {
            return 'Contact is not subscribed.';
        }
        if (! $contact->consented_at || ! $contact->legal_basis) {
            return 'Consent and legal basis are required.';
        }
        if (Suppression::withoutGlobalScope('branch')->where('email', $contact->email)->exists()) {
            return 'Contact is suppressed.';
        }

        return null;
    }
}
