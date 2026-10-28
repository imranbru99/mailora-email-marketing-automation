<?php

namespace App\Filament\Resources;

use App\Models\Deal;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class DealResource extends TenantResource
{
    protected static ?string $model = Deal::class;

    public static string $module = 'crm';

    protected static ?string $navigationGroup = 'Sales & Team';

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    public static function form(Form $form): Form
    {
        return $form->schema([TextInput::make('name')->required(), Select::make('stage')->options(['new' => 'New lead', 'qualified' => 'Qualified', 'proposal' => 'Proposal', 'won' => 'Won', 'lost' => 'Lost'])->default('new')->required(), TextInput::make('value_minor')->numeric()->default(0), TextInput::make('currency')->length(3)->default('USD'), DateTimePicker::make('expected_close'), Textarea::make('notes')])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable()->sortable(), Tables\Columns\TextColumn::make('stage')->searchable()->sortable()->badge(), Tables\Columns\TextColumn::make('value_minor')->searchable()->sortable(), Tables\Columns\TextColumn::make('currency')->searchable()->sortable(), Tables\Columns\TextColumn::make('expected_close')->searchable()->sortable(), Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true)])->filters([])->actions([Tables\Actions\ActionGroup::make([Tables\Actions\EditAction::make()])])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => DealResource\Pages\ListRecords::route('/'), 'create' => DealResource\Pages\CreateRecord::route('/create'), 'edit' => DealResource\Pages\EditRecord::route('/{record}/edit')];
    }
}
