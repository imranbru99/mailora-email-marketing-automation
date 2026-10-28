<?php

namespace App\Filament\Widgets;

use App\Models\Campaign;
use App\Services\ModuleRegistry;
use Filament\Widgets\ChartWidget;

class CampaignPerformance extends ChartWidget
{
    protected static ?string $heading = 'Campaign performance';

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return app(ModuleRegistry::class)->enabled('campaigns');
    }

    protected function getData(): array
    {
        $labels = [];
        $sent = [];
        $opened = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('M');
            $q = Campaign::whereBetween('created_at', [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()]);
            $sent[] = (clone $q)->sum('sent_count');
            $opened[] = (clone $q)->sum('open_count');
        }

        return ['datasets' => [['label' => 'Sent', 'data' => $sent, 'borderColor' => '#635bff', 'backgroundColor' => '#635bff22', 'fill' => true, 'tension' => 0.4], ['label' => 'Opened', 'data' => $opened, 'borderColor' => '#14b8a6', 'tension' => 0.4]], 'labels' => $labels];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
