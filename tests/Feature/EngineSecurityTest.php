<?php

namespace Tests\Feature;

use App\Contracts\TextGenerator;
use App\Jobs\PrepareCampaign;
use App\Jobs\SendMessage;
use App\Models\AiGeneration;
use App\Models\Automation;
use App\Models\AutomationRun;
use App\Models\Branch;
use App\Models\Business;
use App\Models\BusinessRole;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\MessageLog;
use App\Models\Module;
use App\Models\SendingIdentity;
use App\Models\Template;
use App\Models\UsageCounter;
use App\Models\User;
use App\Services\AiService;
use App\Services\AutomationEngine;
use App\Services\CampaignService;
use App\Services\ModuleRegistry;
use App\Services\TemplateRenderer;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EngineSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::create(['name' => 'Test', 'preset' => 'Custom']);
        $this->branch = Branch::create(['business_id' => $this->business->id, 'name' => 'Main']);
        app(TenantContext::class)->set($this->business->id, $this->branch->id);
        foreach (config('modules') as $definition) {
            Module::firstOrCreate(['key' => $definition['key']], array_intersect_key($definition, array_flip(['key', 'name', 'description', 'category', 'depends_on', 'is_core', 'default_enabled'])));
        }
    }

    private function contact(string $email = 'one@example.test'): Contact
    {
        return Contact::create(['email' => $email, 'first_name' => 'Test', 'status' => 'subscribed', 'consented_at' => now(), 'legal_basis' => 'consent']);
    }

    private function campaign(): Campaign
    {
        $sender = SendingIdentity::create(['name' => 'Sender', 'email' => 'sender@example.test', 'domain' => 'example.test', 'verified_at' => now(), 'postal_address' => '12 Road']);

        return Campaign::create(['name' => 'Test', 'subject' => 'Hello', 'html' => '<a href="{{unsubscribe_url}}">Unsubscribe</a>', 'sending_identity_id' => $sender->id, 'approved_at' => now(), 'status' => 'sending']);
    }

    public function test_automation_email_uses_content_approved_at_publication(): void
    {
        Queue::fake();
        $contact = $this->contact();
        $sender = $this->campaign()->identity;
        $template = Template::create(['name' => 'Welcome', 'category' => 'Welcome', 'subject' => 'Approved subject', 'html' => '<p>Approved copy</p><a href="{{unsubscribe_url}}">Leave</a>']);
        $flow = Automation::create(['name' => 'Welcome', 'status' => 'live', 'trigger' => 'manual', 'steps' => [['type' => 'email', 'value' => (string) $template->id, 'identity_id' => $sender->id]]]);
        $template->update(['subject' => 'Changed after approval', 'html' => '<p>Unapproved copy</p>']);
        $engine = app(AutomationEngine::class);
        $engine->enroll($contact, 'manual');
        $run = AutomationRun::firstOrFail();
        $engine->tick($run->id);
        $campaign = Campaign::where('automation_run_id', $run->id)->firstOrFail();
        $this->assertSame('Approved subject', $campaign->subject);
        $this->assertStringContainsString('Approved copy', $campaign->html);
        $this->assertStringNotContainsString('Unapproved copy', $campaign->html);
        Queue::assertPushed(SendMessage::class, 1);
        $engine->tick($run->id);
        $this->assertSame(1, Campaign::where('automation_run_id', $run->id)->count());
    }

    public function test_all_branches_enrollment_does_not_use_another_branches_flow(): void
    {
        $contact = $this->contact();
        $flow = Automation::create(['name' => 'Own branch', 'status' => 'live', 'trigger' => 'manual', 'steps' => [['type' => 'tag', 'value' => 'correct']]]);
        $otherBranch = Branch::create(['business_id' => $this->business->id, 'name' => 'Other']);
        app(TenantContext::class)->set($this->business->id, $otherBranch->id);
        Automation::create(['name' => 'Other branch', 'status' => 'live', 'trigger' => 'manual', 'steps' => [['type' => 'tag', 'value' => 'wrong']]]);
        app(TenantContext::class)->set($this->business->id);
        app(AutomationEngine::class)->enroll($contact, 'manual');
        $this->assertSame(1, AutomationRun::count());
        $this->assertSame($flow->id, AutomationRun::first()->automation_id);
    }

    public function test_block_templates_preserve_a_functional_unsubscribe_link(): void
    {
        $renderer = app(TemplateRenderer::class);
        $template = $renderer->blocks([['type' => 'heading', 'data' => ['text' => 'Hello']]]);
        $this->assertStringContainsString('href="{{unsubscribe_url}}"', $template);
        $contact = $this->contact();
        $rendered = $renderer->render($template, $contact);
        $this->assertStringContainsString(route('unsubscribe', ['token' => $contact->unsubscribe_token]), $rendered);
        $this->assertStringNotContainsString('merge-unsubscribe', $rendered);
    }

    public function test_loaded_contact_cannot_be_saved_after_tenant_switch(): void
    {
        $contact = $this->contact();
        $other = Business::create(['name' => 'Other', 'preset' => 'Custom']);
        app(TenantContext::class)->set($other->id);
        $this->expectException(\LogicException::class);
        $contact->update(['first_name' => 'Changed']);
    }

    public function test_loaded_contact_cannot_be_deleted_after_tenant_switch(): void
    {
        $contact = $this->contact();
        $other = Business::create(['name' => 'Other', 'preset' => 'Custom']);
        app(TenantContext::class)->set($other->id);
        $this->expectException(\LogicException::class);
        $contact->delete();
    }

    public function test_forcing_business_id_does_not_bypass_original_tenant_check(): void
    {
        $contact = $this->contact();
        $other = Business::create(['name' => 'Other', 'preset' => 'Custom']);
        app(TenantContext::class)->set($other->id);
        $this->expectException(\LogicException::class);
        $contact->forceFill(['business_id' => $other->id, 'branch_id' => null])->save();
    }

    public function test_loaded_contact_cannot_be_moved_by_switching_branches(): void
    {
        $contact = $this->contact();
        $other = Branch::create(['business_id' => $this->business->id, 'name' => 'Other']);
        app(TenantContext::class)->set($this->business->id, $other->id);
        $this->expectException(\LogicException::class);
        $contact->update(['first_name' => 'Changed']);
    }

    public function test_loaded_contact_cannot_be_deleted_in_another_branch(): void
    {
        $contact = $this->contact();
        $other = Branch::create(['business_id' => $this->business->id, 'name' => 'Other']);
        app(TenantContext::class)->set($this->business->id, $other->id);
        $this->expectException(\LogicException::class);
        $contact->delete();
    }

    public function test_contact_deleted_after_queueing_is_suppressed_without_poisoning_the_job(): void
    {
        $contact = $this->contact();
        $campaign = $this->campaign();
        $message = MessageLog::create(['contact_id' => $contact->id, 'campaign_id' => $campaign->id]);
        $contact->delete();
        (new SendMessage($this->business->id, $this->branch->id, $message->id))->handle();
        $this->assertSame('suppressed', $message->fresh()->status);
        $this->assertSame('sent', $campaign->fresh()->status);
        $this->assertSame(0, $campaign->fresh()->sent_count);
    }

    public function test_api_cursor_pages_without_losing_or_exposing_the_internal_id(): void
    {
        for ($i = 1; $i <= 101; $i++) {
            $this->contact('contact'.$i.'@example.test');
        }
        $user = User::factory()->create(['business_id' => $this->business->id, 'role' => 'Business Owner']);
        Sanctum::actingAs($user, ['contacts:read']);
        $first = $this->getJson('/api/v1/contacts')->assertOk()->assertJsonCount(100, 'data');
        $this->assertArrayNotHasKey('id', $first->json('data.0'));
        $next = $first->json('next_page_url');
        $this->assertNotNull($next);
        $this->getJson($next)->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.email', 'contact101@example.test');
    }

    public function test_token_abilities_do_not_override_the_users_role(): void
    {
        BusinessRole::create(['business_id' => $this->business->id, 'name' => 'Restricted', 'permissions' => []]);
        $user = User::factory()->create(['business_id' => $this->business->id, 'role' => 'Restricted']);
        Sanctum::actingAs($user, ['contacts:read', 'events:write']);
        $contact = $this->contact();
        $this->getJson('/api/v1/contacts')->assertForbidden();
        $this->postJson('/api/v1/events', ['contact_id' => $contact->public_id, 'event' => 'manual'])->assertForbidden();
    }

    public function test_api_duplicate_check_includes_deleted_contacts(): void
    {
        $contact = $this->contact();
        $contact->delete();
        $user = User::factory()->create(['business_id' => $this->business->id, 'role' => 'Business Owner']);
        Sanctum::actingAs($user, ['contacts:write']);
        $this->postJson('/api/v1/contacts', ['email' => $contact->email, 'first_name' => 'Again'])->assertStatus(409);
    }

    public function test_preparation_never_sends_scheduled_campaigns_early(): void
    {
        Queue::fake();
        $this->contact();
        $campaign = $this->campaign();
        $campaign->update(['status' => 'scheduled', 'scheduled_at' => now()->addHour()]);
        (new PrepareCampaign($this->business->id, $this->branch->id, $campaign->id))->handle();
        $this->assertSame('scheduled', $campaign->fresh()->status);
        $this->assertSame(0, MessageLog::count());
        Queue::assertNothingPushed();
    }

    public function test_repreparing_active_campaign_does_not_add_new_recipients(): void
    {
        Queue::fake();
        $this->contact();
        $campaign = $this->campaign();
        $job = new PrepareCampaign($this->business->id, $this->branch->id, $campaign->id);
        $job->handle();
        $this->contact('new@example.test');
        $job->handle();
        $this->assertSame(1, MessageLog::count());
        $this->assertSame('sending', $campaign->fresh()->status);
    }

    public function test_launch_revalidates_persisted_approval_instead_of_stale_model(): void
    {
        Queue::fake();
        $user = User::factory()->create(['business_id' => $this->business->id, 'role' => 'Business Owner']);
        $this->actingAs($user);
        $campaign = $this->campaign();
        $campaign->update(['status' => 'draft']);
        Campaign::whereKey($campaign->id)->update(['approved_at' => null]);
        $this->expectException(ValidationException::class);
        app(CampaignService::class)->launch($campaign);
    }

    public function test_duplicate_delivery_job_does_not_consume_send_rate(): void
    {
        $this->business->update(['settings' => ['send_per_minute' => 2]]);
        $one = $this->contact();
        $two = $this->contact('two@example.test');
        $campaign = $this->campaign();
        $messageOne = MessageLog::create(['contact_id' => $one->id, 'campaign_id' => $campaign->id]);
        $messageTwo = MessageLog::create(['contact_id' => $two->id, 'campaign_id' => $campaign->id]);
        $first = new SendMessage($this->business->id, $this->branch->id, $messageOne->id);
        $first->handle();
        $first->handle();
        (new SendMessage($this->business->id, $this->branch->id, $messageTwo->id))->handle();
        $this->assertSame('sandbox', $messageTwo->fresh()->status);
        $this->assertSame(2, RateLimiter::attempts('send:'.$this->business->id));
    }

    public function test_ai_completion_settles_original_month_and_duplicate_does_not_overwrite_result(): void
    {
        app(ModuleRegistry::class)->toggle('ai-studio', true);
        $this->business->update(['settings' => ['ai_enabled' => true, 'ai_token_limit' => 10000]]);
        $this->travelTo(now()->setDate(2026, 10, 31)->endOfDay());
        $generation = AiGeneration::create(['type' => 'copy', 'prompt' => 'New product']);
        $driver = new class implements TextGenerator
        {
            public int $attempts = 0;

            public function generate(string $prompt, string $provider, ?string $model = null): array
            {
                $this->attempts++;
                Carbon::setTestNow(now()->addMinute());

                return ['content' => 'Done', 'tokens' => 750, 'cost_minor' => 2];
            }
        };
        $this->app->instance(TextGenerator::class, $driver);
        app(AiService::class)->execute($generation);
        $this->assertSame(750, UsageCounter::where('period', '2026-10')->value('ai_tokens'));
        $this->assertFalse(UsageCounter::where('period', '2026-11')->exists());
        $this->business->update(['settings' => ['ai_enabled' => false]]);
        app(AiService::class)->execute($generation);
        $this->assertSame(1, $driver->attempts);
        $this->assertSame('completed', $generation->fresh()->status);
        $this->assertSame('Done', $generation->fresh()->result);
    }

    public function test_ai_reservation_accounts_for_multibyte_prompt_before_provider_call(): void
    {
        app(ModuleRegistry::class)->toggle('ai-studio', true);
        $this->business->update(['settings' => ['ai_enabled' => true, 'ai_token_limit' => 4000]]);
        $generation = AiGeneration::create(['type' => 'copy', 'prompt' => str_repeat('অ', 2000)]);
        $this->mock(TextGenerator::class)->shouldNotReceive('generate');
        app(AiService::class)->execute($generation);
        $this->assertSame('quota_exceeded', $generation->fresh()->status);
        $this->assertSame(0, DB::table('usage_counters')->value('ai_tokens'));
    }
}
