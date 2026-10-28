<?php

namespace App\Filament\Widgets;

use App\Models\Automation;
use App\Models\Campaign;
use App\Models\Contact;
use App\Services\ModuleRegistry;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class Overview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $stats = [];
        $modules = app(ModuleRegistry::class);
        if ($modules->enabled('contacts')) {
            $stats[] = Stat::make('Your audience', number_format(Contact::count()))->description(number_format(Contact::where('status', 'subscribed')->count()).' subscribed contacts')->descriptionIcon('heroicon-m-user-group')->color('primary');
        }
        if ($modules->enabled('campaigns')) {
            $sent = Campaign::sum('sent_count');
            $opens = Campaign::sum('open_count');
            $stats[] = Stat::make('Emails sent', number_format($sent))->description('Sandbox + seeded history')->color('success');
            $stats[] = Stat::make('Open rate', $sent ? round($opens / $sent * 100, 1).'%' : '—')->description('Unique opens across campaigns')->color('primary');
        }
        if ($modules->enabled('automation')) {
            $stats[] = Stat::make('Active automations', Automation::where('status', 'live')->count())->description('Working for your business')->color('success');
        }

        return $stats;
    }
}
