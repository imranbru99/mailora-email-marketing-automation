<?php

namespace Tests\Feature;

use App\Contracts\MailDelivery;
use App\Filament\Resources\SurveyResource;
use App\Jobs\ImportContacts;
use App\Jobs\SendMessage;
use App\Models\Automation;
use App\Models\AutomationRun;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\ContactImport;
use App\Models\Coupon;
use App\Models\Invoice;
use App\Models\MessageLog;
use App\Models\Module;
use App\Models\SendingIdentity;
use App\Models\SignupForm;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Models\Webinar;
use App\Models\WebinarRegistration;
use App\Services\AutomationEngine;
use App\Services\ContactService;
use App\Services\CouponService;
use App\Services\InvoiceCalculator;
use App\Services\ModuleRegistry;
use App\Services\PresetService;
use App\Services\SegmentEngine;
use App\Services\TemplateRenderer;
use App\Services\TenantContext;
use App\Services\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::create(['name' => 'Test business', 'preset' => 'Custom']);
        $this->branch = Branch::create(['business_id' => $this->business->id, 'name' => 'Head office']);
        app(TenantContext::class)->set($this->business->id, $this->branch->id);
        foreach (config('modules') as $m) {
            Module::firstOrCreate(['key' => $m['key']], array_intersect_key($m, array_flip(['key', 'name', 'description', 'category', 'depends_on', 'is_core', 'default_enabled'])));
        }
    }

    private function contact(array $data = []): Contact
    {
        return Contact::create(array_merge(['first_name' => 'Ayesha', 'email' => 'ayesha@example.test', 'status' => 'subscribed', 'consented_at' => now(), 'legal_basis' => 'consent'], $data));
    }

    private function campaign(): Campaign
    {
        $sender = SendingIdentity::create(['name' => 'Team', 'email' => 'team@example.test', 'domain' => 'example.test', 'verified_at' => now(), 'postal_address' => '12 Road, Dhaka']);

        return Campaign::create(['name' => 'Welcome', 'subject' => 'Hello', 'html' => '<p>Hello {{first_name}}</p><a href="{{unsubscribe_url}}">Unsubscribe</a>', 'sending_identity_id' => $sender->id, 'approved_at' => now(), 'status' => 'sending']);
    }

    public function test_scoping_fails_closed_without_context_and_isolates_businesses(): void
    {
        $this->contact();
        $other = Business::create(['name' => 'Other', 'preset' => 'Custom']);
        app(TenantContext::class)->set($other->id);
        $this->assertSame(0, Contact::count());
        app(TenantContext::class)->businessId = null;
        $this->assertSame(0, Contact::count());
    }

    public function test_module_dependencies_and_preservation(): void
    {
        $contact = $this->contact();
        $registry = app(ModuleRegistry::class);
        $registry->toggle('tracking', false);
        $registry->toggle('campaign-reports', false);
        $registry->toggle('campaigns', false);
        $this->assertFalse($registry->enabled('campaigns'));
        $this->assertSame(1, Contact::count());
        $registry->toggle('campaigns', true);
        $this->assertTrue($registry->enabled('campaigns'));
    }

    public function test_core_cannot_be_disabled(): void
    {
        $this->expectException(ValidationException::class);
        app(ModuleRegistry::class)->toggle('audit', false);
    }

    public function test_segments_cannot_inject_sql(): void
    {
        $this->expectException(ValidationException::class);
        app(SegmentEngine::class)->query(['rules' => [['field' => 'email); DROP TABLE contacts', 'operator' => '=', 'value' => 'x']]])->get();
    }

    public function test_segment_combines_rules_with_and_or(): void
    {
        $this->contact(['score' => 80]);
        $this->contact(['email' => 'second@example.test', 'score' => 10]);
        $this->assertSame(1, app(SegmentEngine::class)->query(['match' => 'all', 'rules' => [['field' => 'score', 'operator' => '>', 'value' => 70]]])->count());
    }

    public function test_renderer_escapes_fields_and_never_evaluates_php(): void
    {
        $c = $this->contact(['first_name' => '<script>alert(1)</script>']);
        $html = app(TemplateRenderer::class)->render('<p>{{first_name}}</p><script>alert(2)</script>', $c);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_sandbox_send_is_idempotent_and_rechecks_suppression(): void
    {
        $campaign = $this->campaign();
        $contact = $this->contact();
        $message = MessageLog::create(['campaign_id' => $campaign->id, 'contact_id' => $contact->id]);
        $job = new SendMessage($this->business->id, $this->branch->id, $message->id);
        $job->handle();
        $job->handle();
        $this->assertSame('sandbox', $message->fresh()->status);
        $this->assertSame(1, $campaign->fresh()->sent_count);
        $this->assertSame(1, DB::table('usage_counters')->value('emails'));
    }

    public function test_opt_out_is_enforced_even_after_queue_creation(): void
    {
        $c = $this->contact();
        $campaign = $this->campaign();
        $m = MessageLog::create(['campaign_id' => $campaign->id, 'contact_id' => $c->id]);
        app(ContactService::class)->unsubscribe($c);
        (new SendMessage($this->business->id, $this->branch->id, $m->id))->handle();
        $this->assertSame('suppressed', $m->fresh()->status);
        $this->assertSame(0, $campaign->fresh()->sent_count);
    }

    public function test_disabled_module_stops_queued_send(): void
    {
        $c = $this->contact();
        $campaign = $this->campaign();
        $m = MessageLog::create(['campaign_id' => $campaign->id, 'contact_id' => $c->id]);
        DB::table('business_modules')->insert(['business_id' => $this->business->id, 'module_key' => 'campaigns', 'enabled' => false]);
        (new SendMessage($this->business->id, $this->branch->id, $m->id))->handle();
        $this->assertSame('pending', $m->fresh()->status);
    }

    public function test_unsubscribe_still_works_when_module_is_disabled(): void
    {
        $c = $this->contact();
        DB::table('business_modules')->insert(['business_id' => $this->business->id, 'module_key' => 'unsubscribe', 'enabled' => false]);
        $this->get('/unsubscribe/'.$c->unsubscribe_token)->assertOk();
        $this->assertSame('unsubscribed', $c->fresh()->status);
    }

    public function test_automation_snapshots_steps_and_waits(): void
    {
        $c = $this->contact();
        $a = Automation::create(['name' => 'Welcome', 'status' => 'live', 'trigger' => 'contact.created', 'steps' => [['type' => 'tag', 'value' => 'VIP'], ['type' => 'wait', 'value' => '60'], ['type' => 'score', 'value' => '10']]]);
        $e = app(AutomationEngine::class);
        $e->enroll($c, 'contact.created');
        $e->enroll($c, 'contact.created');
        $this->assertSame(1, AutomationRun::count());
        $run = AutomationRun::first();
        $a->update(['steps' => [['type' => 'unsubscribe']]]);
        $e->tick($run->id);
        $this->assertContains('VIP', $c->fresh()->tags);
        $e->tick($run->id);
        $e->tick($run->id);
        $this->assertSame(0, $c->fresh()->score);
        $this->travel(61)->minutes();
        $e->tick($run->id);
        $this->assertSame(10, $c->fresh()->score);
    }

    public function test_analyst_cannot_edit_and_cross_business_branch_is_rejected(): void
    {
        $user = User::factory()->create(['business_id' => $this->business->id, 'role' => 'Analyst']);
        $this->assertFalse($user->allows('contacts', 'edit'));
        $other = Business::create(['name' => 'Other', 'preset' => 'Custom']);
        $branch = Branch::create(['business_id' => $other->id, 'name' => 'Private']);
        $this->actingAs($user)->post('/branch', ['branch_id' => $branch->id])->assertForbidden();
    }

    public function test_signup_does_not_resubscribe_existing_unsubscribe(): void
    {
        $c = $this->contact(['status' => 'unsubscribed']);
        $f = SignupForm::create(['name' => 'Join', 'headline' => 'Join', 'published' => true, 'double_opt_in' => false]);
        $this->post('/forms/'.$f->public_id, ['first_name' => 'Ayesha', 'email' => $c->email, 'consent' => 1])->assertRedirect();
        $this->assertSame('unsubscribed', $c->fresh()->status);
    }

    public function test_form_creates_pending_contact_and_confirmation_requires_signature(): void
    {
        $f = SignupForm::create(['name' => 'Join', 'headline' => 'Join', 'published' => true, 'double_opt_in' => true]);
        $this->post('/forms/'.$f->public_id, ['first_name' => 'Ayesha', 'email' => 'new@example.test', 'consent' => 1])->assertRedirect();
        $c = Contact::first();
        $this->assertSame('pending', $c->status);
        $this->get('/confirm/'.$c->public_id)->assertForbidden();
        $this->get(URL::temporarySignedRoute('confirm', now()->addHour(), ['id' => $c->public_id]))->assertOk();
        $this->assertSame('subscribed', $c->fresh()->status);
    }

    public function test_public_pages_and_admin_render(): void
    {
        foreach (['/', '/features', '/features/contacts', '/pricing', '/templates', '/solutions', '/docs', '/demo', '/admin/login', '/sitemap.xml'] as $url) {
            $this->get($url)->assertOk();
        }
        $u = User::factory()->create(['business_id' => $this->business->id, 'role' => 'Business Owner']);
        foreach (['/admin', '/admin/contacts', '/admin/campaigns', '/admin/templates', '/admin/modules', '/admin/workspace', '/admin/automations/create', '/admin/campaigns/create'] as $url) {
            $this->actingAs($u)->get($url)->assertOk();
        }
    }

    public function test_totp_matches_rfc_6238_and_rejects_replay(): void
    {
        $totp = app(Totp::class);
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        $this->assertSame('94287082', $totp->code($secret, 1, 8));
        $step = intdiv(now()->timestamp, 30);
        $code = $totp->code($secret, $step);
        $this->assertSame($step, $totp->verify($secret, $code));
        $this->assertNull($totp->verify($secret, $code, $step));
    }

    public function test_two_factor_blocks_panel_until_challenge_passes(): void
    {
        $secret = app(Totp::class)->secret();
        $u = User::factory()->create(['business_id' => $this->business->id, 'role' => 'Business Owner', 'two_factor_secret' => $secret]);
        $this->actingAs($u)->get('/admin')->assertRedirect('/two-factor');
        $this->post('/two-factor', ['code' => app(Totp::class)->code($secret, intdiv(now()->timestamp, 30))])->assertRedirect('/admin');
        $this->get('/admin')->assertOk();
        $this->assertNotSame($secret, DB::table('users')->where('id', $u->id)->value('two_factor_secret'));
    }

    public function test_recovery_code_is_single_use(): void
    {
        $u = User::factory()->create(['business_id' => $this->business->id, 'role' => 'Business Owner', 'two_factor_secret' => app(Totp::class)->secret(), 'recovery_codes' => [hash('sha256', 'single-use-code')]]);
        $this->actingAs($u)->post('/two-factor', ['code' => 'single-use-code'])->assertRedirect('/admin');
        $this->assertSame([], $u->fresh()->recovery_codes);
        $this->post('/two-factor', ['code' => 'single-use-code'])->assertSessionHasErrors('code');
    }

    public function test_survey_is_module_gated_and_deduplicates_session(): void
    {
        app(ModuleRegistry::class)->toggle('surveys', true);
        $survey = Survey::create(['name' => 'Pulse', 'question' => 'How was it?', 'options' => ['Good', 'Great'], 'published' => true]);
        $this->get('/s/'.$survey->public_id)->assertOk();
        $this->post('/s/'.$survey->public_id, ['answer' => 'Great'])->assertRedirect();
        $this->post('/s/'.$survey->public_id, ['answer' => 'Good'])->assertRedirect();
        $this->assertSame(1, SurveyResponse::count());
        app(ModuleRegistry::class)->toggle('surveys', false);
        $this->get('/s/'.$survey->public_id)->assertNotFound();
    }

    public function test_webinar_capacity_and_calendar(): void
    {
        app(ModuleRegistry::class)->toggle('webinars', true);
        $event = Webinar::create(['name' => 'Workshop', 'starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHour(), 'capacity' => 1, 'published' => true]);
        $this->post('/events/'.$event->public_id, ['name' => 'First', 'email' => 'first@example.test'])->assertRedirect();
        $this->post('/events/'.$event->public_id, ['name' => 'Second', 'email' => 'second@example.test'])->assertSessionHasErrors('email');
        $this->get('/events/'.$event->public_id.'/calendar')->assertOk()->assertSee('BEGIN:VCALENDAR');
        $this->assertSame(1, WebinarRegistration::count());
    }

    public function test_coupon_redemption_is_atomic_and_idempotent(): void
    {
        app(ModuleRegistry::class)->toggle('coupons', true);
        $contact = $this->contact();
        $coupon = Coupon::create(['name' => 'Welcome', 'code' => 'WELCOME', 'discount_percent' => 10, 'max_uses' => 1, 'expires_at' => now()->addDay()]);
        $service = app(CouponService::class);
        $one = $service->redeem('WELCOME', $contact);
        $two = $service->redeem('WELCOME', $contact);
        $this->assertSame($one->id, $two->id);
        $this->assertSame(1, $coupon->fresh()->uses);
        $this->expectException(ValidationException::class);
        $service->redeem('WELCOME', $this->contact(['email' => 'other@example.test']));
    }

    public function test_invoice_uses_minor_units_and_rounds_tax_once(): void
    {
        $invoice = Invoice::create(['name' => 'INV-1', 'items' => [['description' => 'Design', 'quantity' => 3, 'unit_price_minor' => 1050]], 'tax_percent' => 5]);
        $this->assertSame(3308, app(InvoiceCalculator::class)->total($invoice));
    }

    public function test_import_deduplicates_and_never_infers_consent(): void
    {
        app(ModuleRegistry::class)->toggle('imports', true);
        Storage::fake('local');
        $path = 'imports/'.$this->business->id.'/contacts.csv';
        Storage::disk('local')->put($path, "email,first_name\nNEW@example.test,New\nnew@example.test,Duplicate\ninvalid,Broken\n");
        $import = ContactImport::create(['path' => $path]);
        $job = new ImportContacts($this->business->id, $this->branch->id, $import->id);
        $job->handle();
        $job->handle();
        $this->assertSame(1, Contact::count());
        $this->assertSame('pending', Contact::first()->status);
        $this->assertNull(Contact::first()->consented_at);
        $this->assertSame(2, $import->fresh()->skipped);
    }

    public function test_signed_tracking_link_cannot_be_retargeted_and_counts_uniquely(): void
    {
        $contact = $this->contact();
        $campaign = $this->campaign();
        $campaign->update(['html' => '<a href="https://imrandev.bd">Read</a><a href="{{unsubscribe_url}}">Unsubscribe</a>']);
        $message = MessageLog::create(['campaign_id' => $campaign->id, 'contact_id' => $contact->id]);
        (new SendMessage($this->business->id, $this->branch->id, $message->id))->handle();
        $link = DB::table('tracking_links')->first();
        $url = URL::signedRoute('track.click', ['token' => $link->token]);
        $this->get($url)->assertRedirect('https://imrandev.bd');
        $this->get($url)->assertRedirect('https://imrandev.bd');
        $this->assertSame(1, $campaign->fresh()->click_count);
        $this->get($url.'&destination=https://evil.example')->assertForbidden();
    }

    public function test_changed_sender_domain_invalidates_verification(): void
    {
        $campaign = $this->campaign();
        $sender = $campaign->identity;
        $sender->update(['dns_verified_at' => now()]);
        $sender->update(['domain' => 'new.example.test']);
        $this->assertNull($sender->fresh()->dns_verified_at);
        $this->assertNull($sender->fresh()->verified_at);
    }

    public function test_live_transport_uncertain_outcome_is_not_retried(): void
    {
        config(['platform.sandbox' => false, 'mail.default' => 'smtp', 'app.url' => 'https://mail.example.test']);
        $contact = $this->contact();
        $campaign = $this->campaign();
        $campaign->identity->update(['dns_verified_at' => now()]);
        $message = MessageLog::create(['campaign_id' => $campaign->id, 'contact_id' => $contact->id]);
        $attempts = 0;
        $driver = new class($attempts) implements MailDelivery
        {
            public int $attempts = 0;

            public function __construct(&$attempts)
            {
                $this->attempts = &$attempts;
            }

            public function send(array $message): void
            {
                $this->attempts++;
                throw new \RuntimeException('Connection dropped after acceptance.');
            }
        };
        $this->app->instance(MailDelivery::class, $driver);
        $job = new SendMessage($this->business->id, $this->branch->id, $message->id);
        $job->handle();
        $job->handle();
        $this->assertSame(1, $attempts);
        $this->assertSame('uncertain', $message->fresh()->status);
        $this->assertSame('paused', $campaign->fresh()->status);
    }

    public function test_api_abilities_and_business_scope(): void
    {
        $this->contact();
        $u = User::factory()->create(['business_id' => $this->business->id, 'role' => 'Business Owner']);
        Sanctum::actingAs($u, ['contacts:read']);
        $this->getJson('/api/v1/contacts')->assertOk()->assertJsonCount(1, 'data');
        $this->postJson('/api/v1/contacts', ['email' => 'api@example.test', 'first_name' => 'API'])->assertForbidden();
    }

    public function test_new_growth_resources_render_and_reject_analyst_mutation(): void
    {
        $u = User::factory()->create(['business_id' => $this->business->id, 'role' => 'Business Owner']);
        foreach (['landing-pages', 'surveys', 'webinars', 'coupons', 'short-links', 'snippets', 'tickets', 'knowledge-base', 'invoices', 'custom-fields', 'attendance', 'time-tracking'] as $key) {
            app(ModuleRegistry::class)->toggle($key, true);
        }
        foreach (['landing-pages', 'surveys', 'webinars', 'coupons', 'short-links', 'snippets', 'tickets', 'articles', 'invoices', 'custom-fields', 'attendance-entries', 'time-entries', 'users', 'branches', 'business-roles', 'security', 'audit-trail', 'delivery-log'] as $path) {
            $this->actingAs($u)->get('/admin/'.$path)->assertOk();
        }
        $u->forceFill(['role' => 'Analyst'])->save();
        $this->actingAs($u)->get('/admin/users')->assertForbidden();
        $this->assertFalse(SurveyResource::canCreate());
    }

    public function test_all_presets_resolve_dependencies_and_core_modules_remain_enabled(): void
    {
        foreach (config('platform.presets') as $preset) {
            app(PresetService::class)->apply($preset);
            $this->assertTrue(app(ModuleRegistry::class)->enabled('audit'));
            $this->assertTrue(app(ModuleRegistry::class)->enabled('campaigns'));
        }
    }
}
