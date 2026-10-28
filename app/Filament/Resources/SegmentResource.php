<?php

namespace App\Filament\Resources;

use App\Models\Segment;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class SegmentResource extends TenantResource
{
    protected static ?string $model = Segment::class;

    public static string $module = 'segments';

    protected static ?string $navigationGroup = 'Audience';

    protected static ?string $navigationIcon = 'heroicon-o-funnel';

    public static function form(Form $form): Form
    {
        return $form->schema([TextInput::make('name')->required(), Section::make('Conditions')->schema([
            Select::make('conditions.match')->options(['all' => 'Match all', 'any' => 'Match any'])->default('all'),
            Repeater::make('conditions.rules')->schema([Select::make('field')->options(array_combine(['email', 'company', 'status', 'locale', 'score', 'source'], ['Email', 'Company', 'Status', 'Language', 'Score', 'Source']))->required(), Select::make('operator')->options(['=' => 'Equals', '!=' => 'Does not equal', '>' => 'Greater than', '<' => 'Less than', 'contains' => 'Contains'])->required()->default('='), TextInput::make('value')->required()])->columns(3)->defaultItems(1),
        ])->columnSpanFull(), ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable()->sortable(), Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true)])->filters([])->actions([Tables\Actions\ActionGroup::make([Tables\Actions\EditAction::make()])])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => SegmentResource\Pages\ListRecords::route('/'), 'create' => SegmentResource\Pages\CreateRecord::route('/create'), 'edit' => SegmentResource\Pages\EditRecord::route('/{record}/edit')];
    }
}
