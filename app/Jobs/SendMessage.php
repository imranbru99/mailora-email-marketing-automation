<?php

namespace App\Jobs;

use App\Contracts\MailDelivery;
use App\Models\Business;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\MessageLog;
use App\Models\UsageCounter;
use App\Services\CampaignService;
use App\Services\ComplianceGuard;
use App\Services\ModuleRegistry;
use App\Services\TemplateRenderer;
use App\Services\TenantContext;
use App\Services\TrackingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;

class SendMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 0;

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addDay();
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 30, 120, 300];
    }

    public function __construct(public int $businessId, public ?int $branchId, public int $messageId) {}

    public function handle(): void
    {
        app(TenantContext::class)->run($this->businessId, $this->branchId, function () {
            if (! app(ModuleRegistry::class)->enabled('campaigns')) {
                return;
            }
            $payload = DB::transaction(function () {
                $business = Business::lockForUpdate()->findOrFail($this->businessId);
                $message = MessageLog::lockForUpdate()->find($this->messageId);
                if (! $message || $message->status !== 'pending') {
                    return;
                }
                $campaign = Campaign::with('identity')->lockForUpdate()->find($message->campaign_id);
                if (! $campaign || $campaign->status !== 'sending') {
                    return;
                }
                if (app(ComplianceGuard::class)->campaignErrors($campaign)) {
                    $campaign->update(['status' => 'paused']);

                    return;
                }
                $contact = Contact::find($message->contact_id);
                $reason = $contact ? app(ComplianceGuard::class)->contactError($contact) : 'Contact was deleted.';
                if ($reason) {
                    $message->update(['status' => 'suppressed', 'reason' => $reason]);
                    app(CampaignService::class)->finish($campaign);

                    return;
                }
                $usage = UsageCounter::firstOrCreate(['business_id' => $this->businessId, 'period' => now()->format('Y-m')]);
                if ($usage->emails >= $business->monthly_email_limit) {
                    $campaign->update(['status' => 'paused']);

                    return;
                }
                $rateKey = 'send:'.$this->businessId;
                $limit = max(1, (int) (app(TenantContext::class)->settings()['send_per_minute'] ?? 120));
                if (RateLimiter::tooManyAttempts($rateKey, $limit)) {
                    $this->release(60);

                    return;
                }
                RateLimiter::hit($rateKey, 60);
                $html = app(TemplateRenderer::class)->render($campaign->html, $contact).'<p>'.e($campaign->identity->postal_address).'</p>';
                if ($campaign->tracking && app(ModuleRegistry::class)->enabled('tracking')) {
                    $html = app(TrackingService::class)->links($html, $message);
                    $html .= '<img width="1" height="1" alt="" src="'.e(URL::signedRoute('track.open', ['id' => $message->public_id])).'">';
                }
                $sandbox = config('platform.sandbox');
                $message->update(['status' => $sandbox ? 'sandbox' : 'dispatching', 'recipient_email' => $contact->email, 'rendered_html' => $html, 'rendered_text' => trim(html_entity_decode(strip_tags($html))), 'headers' => ['List-Unsubscribe' => '<'.route('unsubscribe', ['token' => $contact->unsubscribe_token]).'>', 'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click'], 'sent_at' => $sandbox ? now() : null]);
                $usage->increment('emails');
                if (! $sandbox) {
                    return ['to' => $contact->email, 'from' => $campaign->identity->email, 'name' => $campaign->identity->name, 'subject' => $campaign->subject, 'html' => $html, 'text' => trim(html_entity_decode(strip_tags($html))), 'headers' => $message->headers, 'message_id' => $message->public_id.'@'.$campaign->identity->domain];
                }
                $campaign->increment('sent_count');
                app(CampaignService::class)->finish($campaign);
            }, 3);
            if (! $payload) {
                return;
            }
            try {
                app(MailDelivery::class)->send($payload);
                DB::transaction(function () {
                    Business::lockForUpdate()->findOrFail($this->businessId);
                    $message = MessageLog::lockForUpdate()->findOrFail($this->messageId);
                    if ($message->status !== 'dispatching') {
                        return;
                    }
                    $message->update(['status' => 'sent', 'sent_at' => now()]);
                    $campaign = Campaign::lockForUpdate()->findOrFail($message->campaign_id);
                    $campaign->increment('sent_count');
                    app(CampaignService::class)->finish($campaign);
                });
            } catch (\Throwable $e) {
                MessageLog::whereKey($this->messageId)->update(['status' => 'uncertain', 'reason' => 'SMTP outcome requires operator review. Automatic retry is disabled to prevent duplicate delivery.']);
                $message = MessageLog::find($this->messageId);
                if ($message) {
                    Campaign::whereKey($message->campaign_id)->update(['status' => 'paused']);
                }
            }
        });
    }
}
