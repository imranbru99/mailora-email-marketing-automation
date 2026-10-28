<?php

namespace Tests\Feature;

use App\Contracts\MailDomainLookup;
use App\Jobs\CheckEmail;
use App\Models\Business;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\EmailCheck;
use App\Models\FeedbackEndpoint;
use App\Models\FeedbackEvent;
use App\Models\MessageLog;
use App\Models\Module;
use App\Models\ReviewComment;
use App\Models\Suppression;
use App\Models\User;
use App\Services\CampaignPreflight;
use App\Services\CampaignReview;
use App\Services\EmailHygiene;
use App\Services\FeedbackProcessor;
use App\Services\ModuleCatalog;
use App\Services\ModuleRegistry;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CompletionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::create(['name' => 'Business', 'preset' => 'Custom']);
        app(TenantContext::class)->set($this->business->id);
        $this->owner = User::factory()->create(['business_id' => $this->business->id, 'role' => 'Business Owner']);
        $this->actingAs($this->owner);
        foreach (['onboarding', 'feedback', 'verification', 'checklist', 'calendar', 'comments', 'approvals'] as $module) {
            app(ModuleRegistry::class)->toggle($module, true);
        }
    }

    private function campaign(): Campaign
    {
        return Campaign::create(['name' => 'Review me', 'subject' => 'Hello', 'html' => '<p>Hello</p><a href="{{unsubscribe_url}}">Unsubscribe</a>']);
    }

    public function test_fresh_database_has_catalog_without_demo_seeding(): void
    {
        $this->assertSame(108, Module::count());
        app(ModuleCatalog::class)->sync();
        $this->assertSame(108, Module::count());
    }

    public function test_campaign_review_rejection_comments_and_cancel(): void
    {
        $campaign = $this->campaign();
        $review = app(CampaignReview::class);
        $review->request($campaign);
        $this->assertSame('pending approval', $campaign->fresh()->status);
        $review->reject($campaign, 'Please shorten the subject.');
        $this->assertSame('draft', $campaign->fresh()->status);
        $this->assertSame(1, ReviewComment::count());
        $review->comment($campaign, 'Updated and ready.');
        $this->assertSame(2, ReviewComment::count());
        $review->cancel($campaign);
        $this->assertSame('cancelled', $campaign->fresh()->status);
    }

    public function test_preflight_detects_missing_alt_and_unsafe_links(): void
    {
        $campaign = $this->campaign();
        $campaign->update(['html' => '<img src="https://example.com/a.png"><a href="javascript:alert(1)">Go</a>']);
        $report = app(CampaignPreflight::class)->inspect($campaign);
        $this->assertContains('1 link(s) are empty or use an unsupported protocol.', $report['errors']);
        $this->assertContains('1 image(s) need alternative text.', $report['warnings']);
    }

    public function test_signed_feedback_is_idempotent_and_suppresses_original_recipient(): void
    {
        Queue::fake();
        $contact = Contact::create(['email' => 'original@example.test', 'first_name' => 'Reader', 'status' => 'subscribed']);
        $campaign = $this->campaign();
        $message = MessageLog::create(['campaign_id' => $campaign->id, 'contact_id' => $contact->id, 'status' => 'sent', 'recipient_email' => $contact->email, 'sent_at' => now()]);
        $endpoint = FeedbackEndpoint::create(['name' => 'Gateway']);
        $this->assertNotSame($endpoint->secret, DB::table('feedback_endpoints')->value('secret'));
        $body = json_encode(['event_id' => 'receipt-1', 'message_id' => $message->public_id, 'type' => 'hard_bounce']);
        $time = (string) now()->timestamp;
        $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_X_MAILORA_TIMESTAMP' => $time, 'HTTP_X_MAILORA_SIGNATURE' => hash_hmac('sha256', $time.'.'.$body, $endpoint->secret)];
        $this->call('POST', route('feedback.receive', $endpoint->public_id), [], [], [], $headers, $body)->assertAccepted();
        $this->call('POST', route('feedback.receive', $endpoint->public_id), [], [], [], $headers, $body)->assertAccepted();
        $this->assertSame(1, FeedbackEvent::count());
        $contact->update(['email' => 'changed@example.test']);
        $event = FeedbackEvent::firstOrFail();
        app(FeedbackProcessor::class)->process($event->id);
        app(FeedbackProcessor::class)->process($event->id);
        $this->assertSame(1, $campaign->fresh()->bounce_count);
        $this->assertTrue(Suppression::where('email', 'original@example.test')->exists());
        $this->assertFalse(Suppression::where('email', 'changed@example.test')->exists());
    }

    public function test_feedback_rejects_unsigned_and_expired_requests(): void
    {
        $endpoint = FeedbackEndpoint::create(['name' => 'Gateway']);
        $url = route('feedback.receive', $endpoint->public_id);
        $this->postJson($url, [])->assertUnauthorized();
        $body = '{}';
        $time = (string) now()->subMinutes(6)->timestamp;
        $this->call('POST', $url, [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_MAILORA_TIMESTAMP' => $time, 'HTTP_X_MAILORA_SIGNATURE' => hash_hmac('sha256', $time.'.'.$body, $endpoint->secret)], $body)->assertUnauthorized();
        $this->assertSame(0, FeedbackEvent::count());
    }

    public function test_hygiene_checks_are_advisory_and_do_not_subscribe(): void
    {
        $lookup = new class implements MailDomainLookup
        {
            public function exchanges(string $domain): ?array
            {
                return ['mx.example.com'];
            }
        };
        $this->app->instance(MailDomainLookup::class, $lookup);
        $this->assertSame('invalid', app(EmailHygiene::class)->check('invalid')['status']);
        $this->assertSame('review', app(EmailHygiene::class)->check('sales@example.com')['status']);
        $this->assertSame('unknown', app(EmailHygiene::class)->check('reader@example.test')['status']);
        $contact = Contact::create(['email' => 'reader@example.com', 'first_name' => 'Reader', 'status' => 'pending']);
        (new CheckEmail($this->business->id, null, $contact->id))->handle();
        $this->assertSame('valid', EmailCheck::firstOrFail()->status);
        $this->assertSame('pending', $contact->fresh()->status);
        $this->assertNull($contact->fresh()->consented_at);
    }

    public function test_new_workflow_pages_render(): void
    {
        foreach (['/admin/setup-guide', '/admin/campaign-calendar', '/admin/list-hygiene', '/admin/feedback-connections'] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
