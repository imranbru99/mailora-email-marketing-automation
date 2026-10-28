<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class AuditTrail extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationIcon = 'heroicon-o-finger-print';

    protected static string $view = 'filament.pages.table';

    public static function canAccess(): bool
    {
        return auth()->user()?->allows('settings', 'view') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table->query(AuditLog::where('business_id', auth()->user()->business_id))->columns([Tables\Columns\TextColumn::make('action')->searchable()->badge(), Tables\Columns\TextColumn::make('user_id'), Tables\Columns\TextColumn::make('subject_type')->formatStateUsing(fn ($state) => class_basename($state)), Tables\Columns\TextColumn::make('subject_id'), Tables\Columns\TextColumn::make('created_at')->dateTime()])->defaultSort('id', 'desc');
    }
}
