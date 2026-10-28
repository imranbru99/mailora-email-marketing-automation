<?php

namespace App\Filament\Pages;

use App\Models\Campaign;
use App\Services\ExportRequest;
use App\Services\ModuleRegistry;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class CampaignReports extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string $view = 'filament.pages.table';

    protected ?string $subheading = 'Unique opens and clicks are estimates: privacy proxies and bots affect accuracy. Demo campaigns include seeded sample history.';

    public static function canAccess(): bool
    {
        return app(ModuleRegistry::class)->enabled('campaign-reports') && (auth()->user()?->allows('campaign-reports') ?? false);
    }

    public function table(Table $table): Table
    {
        return $table->query(Campaign::query())->columns([Tables\Columns\TextColumn::make('name')->searchable()->wrap(), Tables\Columns\TextColumn::make('status')->badge(), Tables\Columns\TextColumn::make('sent_count')->label('Sent')->numeric()->sortable(), Tables\Columns\TextColumn::make('open_count')->label('Unique opens')->numeric()->sortable(), Tables\Columns\TextColumn::make('open_rate')->state(fn (Campaign $r) => $r->sent_count ? round($r->open_count / $r->sent_count * 100, 1).'%' : '—'), Tables\Columns\TextColumn::make('click_count')->label('Unique clicks')->numeric(), Tables\Columns\TextColumn::make('bounce_count')->numeric()->label('Bounces'), Tables\Columns\TextColumn::make('sent_at')->dateTime()])->filters([Tables\Filters\Filter::make('period')->form([DatePicker::make('from'), DatePicker::make('until')])->query(fn ($query, array $data) => $query->when($data['from'], fn ($q, $d) => $q->whereDate('sent_at', '>=', $d))->when($data['until'], fn ($q, $d) => $q->whereDate('sent_at', '<=', $d)))])->defaultSort('id', 'desc')->actions([Tables\Actions\Action::make('pdf')->label('Queue PDF')->visible(fn () => app(ModuleRegistry::class)->enabled('exports') && auth()->user()->allows('campaign-reports', 'export'))->action(function (Campaign $record) {
            app(ExportRequest::class)->create('campaign-pdf', $record->id);
            Notification::make()->title('Report queued. Open Reports → Exports to download.')->success()->send();
        })]);
    }
}
