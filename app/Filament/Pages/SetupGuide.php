<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\SendingIdentity;
use App\Models\Template;
use App\Services\ModuleRegistry;
use App\Services\TenantContext;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class SetupGuide extends Page
{
    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static string $view = 'filament.pages.setup-guide';

    protected static ?int $navigationSort = -10;

    public static function canAccess(): bool
    {
        return app(ModuleRegistry::class)->enabled('onboarding') && (auth()->user()?->allows('settings', 'edit') ?? false);
    }

    protected function getViewData(): array
    {
        $business = Business::findOrFail(app(TenantContext::class)->businessId);
        $settings = $business->settings ?? [];
        $steps = [
            ['title' => 'Set your business defaults', 'description' => 'Choose the timezone, currency, language, and postal address used in your marketing.', 'url' => '/admin/workspace', 'done' => ! empty($settings['postal_address']) && ! empty($settings['timezone'])],
            ['title' => 'Organize your branches', 'description' => 'Use branches for stores, offices, or client workspaces. Assigned users stay within their branch.', 'url' => '/admin/branches', 'done' => Branch::where('business_id', $business->id)->exists()],
            ['title' => 'Choose your modules', 'description' => 'Review the business preset and enable the workflows you need. Catalog entries may have partial implementation.', 'url' => '/admin/modules', 'done' => DB::table('business_modules')->where('business_id', $business->id)->exists()],
            ['title' => 'Verify a sender', 'description' => config('platform.sandbox') ? 'Sandbox mode: create a sender and use the sandbox verification action.' : 'Live mode: verify domain ownership, SPF, and DMARC. Configure SMTP credentials on the server.', 'url' => '/admin/sending-identities', 'done' => SendingIdentity::whereNotNull(config('platform.sandbox') ? 'verified_at' : 'dns_verified_at')->exists()],
            ['title' => 'Build your consented audience', 'description' => 'Import contacts as pending, or collect signups with confirmation. Consent and suppression are checked before sending.', 'url' => '/admin/contacts', 'done' => Contact::where('status', 'subscribed')->whereNotNull('consented_at')->exists()],
            ['title' => 'Prepare reusable content', 'description' => 'Create a template with an unsubscribe link. Preview the rendered HTML before using it.', 'url' => '/admin/templates', 'done' => Template::exists()],
            ['title' => 'Complete a first campaign', 'description' => 'Draft, review, approve, and queue a small campaign. Keep the queue worker running, then inspect the delivery log.', 'url' => '/admin/campaigns', 'done' => Campaign::where('status', 'sent')->exists()],
        ];

        return ['steps' => $steps, 'completed' => count(array_filter($steps, fn ($step) => $step['done']))];
    }
}
