<?php

namespace App\Filament\Pages;

use App\Models\Campaign;
use App\Services\ModuleRegistry;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;

class CampaignCalendar extends Page
{
    protected static ?string $navigationGroup = 'Campaigns';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.pages.campaign-calendar';

    public string $month = '';

    public static function canAccess(): bool
    {
        return app(ModuleRegistry::class)->enabled('calendar') && (auth()->user()?->allows('campaigns') ?? false);
    }

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
    }

    public function move(int $offset): void
    {
        abort_unless(in_array($offset, [-1, 1]), 422);
        $this->validate(['month' => ['required', 'date_format:Y-m']]);
        $this->month = CarbonImmutable::createFromFormat('!Y-m', $this->month)->addMonths($offset)->format('Y-m');
    }

    protected function getViewData(): array
    {
        $this->validate(['month' => ['required', 'date_format:Y-m']]);
        $start = CarbonImmutable::createFromFormat('!Y-m', $this->month)->startOfMonth()->startOfWeek();
        $end = $start->addDays(41);
        $campaigns = Campaign::whereBetween('scheduled_at', [$start, $end->endOfDay()])->orderBy('scheduled_at')->get()->groupBy(fn (Campaign $c) => $c->scheduled_at?->format('Y-m-d'));

        return ['start' => $start, 'campaigns' => $campaigns];
    }
}
