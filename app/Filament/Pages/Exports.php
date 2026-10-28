<?php

namespace App\Filament\Pages;

use App\Models\DataExport;
use App\Services\ExportRequest;
use App\Services\ModuleRegistry;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class Exports extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static string $view = 'filament.pages.table';

    public static function canAccess(): bool
    {
        return app(ModuleRegistry::class)->enabled('exports') && (auth()->user()?->allows('exports') ?? false);
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('contacts')->label('Export contacts')->visible(fn () => app(ModuleRegistry::class)->enabled('contacts') && auth()->user()->allows('contacts', 'export'))->action(fn () => app(ExportRequest::class)->create('contacts')), Action::make('campaigns')->label('Export campaign metrics')->visible(fn () => app(ModuleRegistry::class)->enabled('campaign-reports') && auth()->user()->allows('campaign-reports', 'export'))->action(fn () => app(ExportRequest::class)->create('campaigns'))];
    }

    public function table(Table $table): Table
    {
        return $table->query(DataExport::where('user_id', auth()->id()))->poll('10s')->columns([Tables\Columns\TextColumn::make('type')->badge(), Tables\Columns\TextColumn::make('status')->badge(), Tables\Columns\TextColumn::make('error')->wrap(), Tables\Columns\TextColumn::make('created_at')->dateTime(), Tables\Columns\TextColumn::make('expires_at')->dateTime()])->defaultSort('id', 'desc')->actions([Tables\Actions\Action::make('download')->url(fn ($record) => route('exports.download', $record->public_id))->visible(fn ($record) => $record->status === 'ready' && $record->expires_at?->isFuture())]);
    }
}
