<?php

namespace App\Filament\Pages;

use App\Models\MessageLog;
use App\Services\ModuleRegistry;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class DeliveryLog extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static string $view = 'filament.pages.table';

    public static function canAccess(): bool
    {
        return app(ModuleRegistry::class)->enabled('queue') && (auth()->user()?->allows('queue') ?? false);
    }

    public function table(Table $table): Table
    {
        return $table->query(MessageLog::query())->columns([Tables\Columns\TextColumn::make('public_id')->label('Message')->searchable()->limit(14), Tables\Columns\TextColumn::make('campaign_id'), Tables\Columns\TextColumn::make('contact_id'), Tables\Columns\TextColumn::make('status')->badge(), Tables\Columns\TextColumn::make('reason')->wrap(), Tables\Columns\TextColumn::make('sent_at')->dateTime()])->defaultSort('id', 'desc')->actions([Tables\Actions\Action::make('preview')->url(fn ($record) => route('message.preview', $record->public_id))->openUrlInNewTab()]);
    }
}
