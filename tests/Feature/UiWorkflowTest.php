<?php

namespace Tests\Feature;

use App\Filament\Pages\ApiTokens;
use App\Jobs\GenerateExport;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Module;
use App\Models\Template;
use App\Models\User;
use App\Services\ExportRequest;
use App\Services\ModuleRegistry;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class UiWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::create(['name' => 'Workspace', 'preset' => 'Custom']);
        $this->owner = User::factory()->create(['business_id' => $this->business->id, 'role' => 'Business Owner', 'password' => 'long-test-password']);
        app(TenantContext::class)->set($this->business->id);
        foreach (config('modules') as $m) {
            Module::firstOrCreate(['key' => $m['key']], array_intersect_key($m, array_flip(['key', 'name', 'description', 'category', 'depends_on', 'is_core', 'default_enabled'])));
        }
        foreach (['api', 'exports', 'campaign-reports'] as $key) {
            app(ModuleRegistry::class)->toggle($key, true);
        }
        $this->actingAs($this->owner);
    }

    public function test_api_token_ui_requires_password_scopes_and_supports_revocation(): void
    {
        $page = Livewire::test(ApiTokens::class)->fillForm(['name' => 'Integration', 'days' => 7, 'abilities' => ['contacts:read'], 'password' => 'wrong'])->call('issue')->assertHasFormErrors(['password']);
        $this->assertSame(0, $this->owner->tokens()->count());
        $page->fillForm(['name' => 'Integration', 'days' => 7, 'abilities' => ['contacts:read'], 'password' => 'long-test-password'])->call('issue')->assertHasNoFormErrors();
        $token = $this->owner->tokens()->firstOrFail();
        $this->assertSame(['contacts:read'], $token->abilities);
        $this->assertNotNull($token->expires_at);
        $this->assertStringContainsString('|', $page->get('issuedToken'));
        $page->call('revoke', $token->id);
        $this->assertSame(0, $this->owner->tokens()->count());
    }

    public function test_queued_export_is_private_formula_safe_and_expires(): void
    {
        Queue::fake();
        Storage::fake('local');
        Contact::create(['email' => 'one@example.test', 'first_name' => '=HYPERLINK("bad")', 'status' => 'pending']);
        $export = app(ExportRequest::class)->create('contacts');
        Queue::assertPushed(GenerateExport::class);
        (new GenerateExport($this->business->id, null, $export->id))->handle();
        $export->refresh();
        $this->assertSame('ready', $export->status);
        $this->assertStringContainsString("'=HYPERLINK", Storage::disk('local')->get($export->path));
        $this->get(route('exports.download', $export->public_id))->assertOk();
        $other = User::factory()->create(['business_id' => $this->business->id, 'role' => 'Business Owner']);
        $this->actingAs($other)->get(route('exports.download', $export->public_id))->assertNotFound();
        $this->actingAs($this->owner);
        $this->travel(25)->hours();
        $this->get(route('exports.download', $export->public_id))->assertNotFound();
        $this->artisan('exports:prune')->assertSuccessful();
        Storage::disk('local')->assertMissing($export->path);
    }

    public function test_export_stops_if_module_disabled_before_worker(): void
    {
        Queue::fake();
        Storage::fake('local');
        $export = app(ExportRequest::class)->create('contacts');
        app(ModuleRegistry::class)->toggle('exports', false);
        (new GenerateExport($this->business->id, null, $export->id))->handle();
        $this->assertSame('cancelled', $export->fresh()->status);
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_public_gallery_never_reads_private_templates(): void
    {
        Template::create(['category' => 'Welcome', 'name' => 'Confidential customer deal', 'subject' => 'Internal only', 'html' => '<p>private</p>']);
        auth()->logout();
        $this->get('/templates')->assertOk()->assertDontSee('Confidential customer deal')->assertDontSee('Internal only')->assertSee('A warm introduction');
    }

    public function test_new_pages_render_and_analyst_cannot_create_tokens(): void
    {
        foreach (['/admin/api-tokens', '/admin/exports', '/admin/campaign-reports', '/admin/workspace', '/admin/contacts/create', '/admin/automations/create'] as $path) {
            $this->get($path)->assertOk();
        }
        $analyst = User::factory()->create(['business_id' => $this->business->id, 'role' => 'Analyst']);
        $this->flushSession();
        $this->actingAs($analyst)->get('/admin/api-tokens')->assertForbidden();
    }

    public function test_template_preview_is_scoped_sanitized_and_sandboxed(): void
    {
        $template = Template::create(['category' => 'Welcome', 'name' => 'Preview', 'subject' => 'Sample', 'html' => '<script>alert(1)</script><p>Hello</p>']);
        $this->get(route('template.preview', $template->public_id))->assertOk()->assertDontSee('<script>', false)->assertHeader('Content-Security-Policy', "sandbox; default-src 'none'; style-src 'unsafe-inline'; img-src data:; base-uri 'none'");
        $other = Business::create(['name' => 'Other', 'preset' => 'Custom']);
        $u = User::factory()->create(['business_id' => $other->id, 'role' => 'Business Owner']);
        $this->actingAs($u)->get(route('template.preview', $template->public_id))->assertNotFound();
    }

    public function test_authenticated_pages_have_no_store_and_referrer_policy(): void
    {
        $this->get('/admin')->assertOk()->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('X-Content-Type-Options','nosniff')->assertHeader('Cache-Control','no-store, private');
    }
}
