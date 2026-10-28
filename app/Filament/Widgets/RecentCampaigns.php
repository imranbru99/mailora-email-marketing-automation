<?php

namespace App\Filament\Widgets;

use App\Models\Campaign;
use App\Services\ModuleRegistry;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentCampaigns extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    protected static ?string $heading = 'Recent campaigns';

    public static function canView(): bool
    {
        return app(ModuleRegistry::class)->enabled('campaigns');
    }

    public function table(Table $table): Table
    {
        return $table->query(Campaign::query()->latest())->columns([Tables\Columns\TextColumn::make('name')->weight('medium'), Tables\Columns\TextColumn::make('status')->badge()->color(fn ($state) => match ($state) {
            'sent' => 'success','draft' => 'gray','scheduled' => 'info',default => 'warning'
        }), Tables\Columns\TextColumn::make('sent_count')->numeric()->label('Sent'), Tables\Columns\TextColumn::make('open_count')->numeric()->label('Opens'), Tables\Columns\TextColumn::make('created_at')->date()])->paginated([5]);
    }
}
