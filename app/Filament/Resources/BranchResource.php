<?php

namespace App\Filament\Resources;

use App\Models\Branch;
use App\Services\TenantContext;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BranchResource extends \Filament\Resources\Resource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    public static function canViewAny(): bool
    {
        return auth()->user()?->allows('settings', 'edit') ?? false;
    }

    /** @return Builder<Branch> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('business_id', app(TenantContext::class)->businessId);
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([TextInput::make('name')->required(), KeyValue::make('settings')->helperText('Overrides: timezone, locale, currency, postal_address. Missing keys inherit business defaults.')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable(), Tables\Columns\TextColumn::make('created_at')->dateTime()])->actions([Tables\Actions\EditAction::make()]);
    }

    /** @return array<string,PageRegistration> */
    public static function getPages(): array
    {
        return ['index' => BranchResource\Pages\ManageBranches::route('/')];
    }
}
