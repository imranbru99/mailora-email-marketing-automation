<?php

namespace App\Filament\Resources;

use App\Models\Suppression;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class SuppressionResource extends TenantResource
{
    protected static ?string $model = Suppression::class;

    public static string $module = 'suppression';

    protected static ?string $navigationGroup = 'Audience';

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    public static function form(Form $form): Form
    {
        return $form->schema([TextInput::make('email')->email()->required(), Select::make('reason')->options(['unsubscribe' => 'Unsubscribe', 'bounce' => 'Hard bounce', 'complaint' => 'Complaint', 'manual' => 'Manual block'])->required()])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('email')->searchable()->sortable(), Tables\Columns\TextColumn::make('reason')->searchable()->sortable(), Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true)])->filters([])->actions([Tables\Actions\ActionGroup::make([Tables\Actions\EditAction::make()])])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => SuppressionResource\Pages\ListRecords::route('/'), 'create' => SuppressionResource\Pages\CreateRecord::route('/create'), 'edit' => SuppressionResource\Pages\EditRecord::route('/{record}/edit')];
    }
}
