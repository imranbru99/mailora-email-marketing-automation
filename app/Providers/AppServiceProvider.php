<?php

namespace App\Providers;

use App\Contracts\AdminTheme;
use App\Contracts\AttendanceGateway;
use App\Contracts\CatalogEnricher;
use App\Contracts\MailDelivery;
use App\Contracts\MailDomainLookup;
use App\Contracts\PerformanceInspector;
use App\Contracts\ReportPdf;
use App\Contracts\Slugger;
use App\Contracts\TextGenerator;
use App\Http\Middleware\RequireTwoFactor;
use App\Http\Middleware\SetTenant;
use App\Services\ModuleRegistry;
use App\Services\TenantContext;
use App\Support\Integrations\AdminPanelAdapter;
use App\Support\Integrations\AiHubAdapter;
use App\Support\Integrations\AttendanceAdapter;
use App\Support\Integrations\CatalogAdapter;
use App\Support\Integrations\DnsMailDomainLookup;
use App\Support\Integrations\DrutogotiAdapter;
use App\Support\Integrations\SmtpDelivery;
use App\Support\Integrations\UnicodePdfAdapter;
use App\Support\Integrations\UniversalSlugAdapter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(TenantContext::class, fn () => new TenantContext);
        $this->app->scoped(ModuleRegistry::class);
        $this->app->bind(MailDomainLookup::class, DnsMailDomainLookup::class);
        $this->app->bind(Slugger::class, UniversalSlugAdapter::class);
        $this->app->bind(MailDelivery::class, SmtpDelivery::class);
        $this->app->bind(ReportPdf::class, UnicodePdfAdapter::class);
        $this->app->bind(TextGenerator::class, AiHubAdapter::class);
        $this->app->bind(CatalogEnricher::class, CatalogAdapter::class);
        $this->app->bind(AttendanceGateway::class, AttendanceAdapter::class);
        $this->app->bind(AdminTheme::class, AdminPanelAdapter::class);
        $this->app->bind(PerformanceInspector::class, DrutogotiAdapter::class);

    }

    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
        Blade::if('module', fn (string $key) => app(ModuleRegistry::class)->enabled($key));
        Gate::define('viewHorizon', fn ($user) => $user->role === 'Super Admin');
        Livewire::addPersistentMiddleware([SetTenant::class, RequireTwoFactor::class]);
    }
}
