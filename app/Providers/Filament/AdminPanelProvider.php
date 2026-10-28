<?php

namespace App\Providers\Filament;

use App\Contracts\AdminTheme;
use App\Filament\Pages\Register;
use App\Http\Middleware\RequireTwoFactor;
use App\Http\Middleware\SetTenant;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()->registration(Register::class)->passwordReset()->profile()
            ->brandName('Mailora')->brandLogo(asset('brand.svg'))->brandLogoHeight('2rem')->favicon(asset('favicon.svg'))
            ->navigationGroups(['Audience', 'Campaigns', 'Content', 'Automation', 'Growth', 'Commerce', 'Sales & Team', 'Reports', 'Billing', 'Settings'])
            ->sidebarCollapsibleOnDesktop()->maxContentWidth('full')
            ->renderHook('panels::head.end', fn () => view('filament.admin-styles'))
            ->renderHook('panels::topbar.start', fn () => view('filament.branch-switcher'))
            ->colors([
                'primary' => Color::hex(app(AdminTheme::class)->primary()),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([

            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                SetTenant::class,
                RequireTwoFactor::class,
            ], isPersistent: true);
    }
}
