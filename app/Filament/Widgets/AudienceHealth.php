<?php

namespace App\Filament\Widgets;

use App\Models\Contact;
use App\Services\ModuleRegistry;
use Filament\Widgets\ChartWidget;

class AudienceHealth extends ChartWidget
{
    protected static ?string $heading = 'Audience health';

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return app(ModuleRegistry::class)->enabled('contacts');
    }

    /** @return array<string,mixed> */
    protected function getData(): array
    {
        return ['datasets' => [['data' => [Contact::where('status', 'subscribed')->count(), Contact::where('status', 'pending')->count(), Contact::where('status', 'unsubscribed')->count()], 'backgroundColor' => ['#635bff', '#f3b555', '#d6d5e4']]], 'labels' => ['Subscribed', 'Pending consent', 'Unsubscribed']];
    }

    /** @return array<string,mixed> */
    protected function getOptions(): array
    {
        return ['scales' => ['x' => ['display' => false], 'y' => ['display' => false]]];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected static ?string $maxHeight = '260px';
}
