<?php

namespace App\Services;

use App\Models\Module;
use Illuminate\Support\Facades\DB;

class PresetService
{
    public function apply(string $preset): void
    {
        $base = ['onboarding', 'contacts', 'lists', 'tags', 'segments', 'suppression', 'consent', 'campaigns', 'templates', 'identities', 'queue', 'unsubscribe', 'email-builder', 'tracking', 'campaign-reports', 'exports', 'imports', 'api', 'events', 'usage', 'tasks', 'forms'];
        $extra = match ($preset) {
            'E-commerce store' => ['products', 'orders', 'coupons', 'automation', 'surveys'],
            'SaaS / Software company' => ['automation', 'crm', 'tickets', 'knowledge-base'],
            'Marketing agency' => ['crm', 'invoices', 'time-tracking', 'attendance', 'landing-pages'],
            'Publisher / Newsletter creator' => ['snippets', 'landing-pages', 'surveys'],
            'Non-profit / Fundraising' => ['landing-pages', 'webinars', 'crm'],
            'Education / Course creator','Events / Webinars' => ['webinars', 'landing-pages', 'automation'],
            'Real estate','Local services' => ['crm', 'landing-pages', 'short-links'],
            'Restaurant / Hospitality' => ['coupons', 'surveys', 'short-links'],
            'Healthcare / Clinic' => ['surveys', 'tasks'],
            'Enterprise' => Module::pluck('key')->all(),
            default => [],
        };
        foreach (Module::all() as $m) {
            DB::table('business_modules')->updateOrInsert(['business_id' => app(TenantContext::class)->businessId, 'module_key' => $m->key], ['enabled' => $m->is_core || in_array($m->key, $base) || in_array($m->key, $extra), 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach (array_unique([...$base, ...$extra]) as $key) {
            app(ModuleRegistry::class)->toggle($key, true);
        }
    }
}
