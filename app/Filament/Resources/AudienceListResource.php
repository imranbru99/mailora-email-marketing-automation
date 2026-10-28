<?php

namespace App\Filament\Resources;

use App\Models\AudienceList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class AudienceListResource extends TenantResource
{
    protected static ?string $model = AudienceList::class;

    public static string $module = 'lists';

    protected static ?string $navigationGroup = 'Audience';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([TextInput::make('name')->required(), Textarea::make('description'), Toggle::make('double_opt_in')->default(true)])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable()->sortable(), Tables\Columns\TextColumn::make('contacts_count')->counts('contacts')->label('Members'), Tables\Columns\IconColumn::make('double_opt_in')->boolean(), Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true)])->filters([])->actions([Tables\Actions\ActionGroup::make([Tables\Actions\EditAction::make()])])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => AudienceListResource\Pages\ListRecords::route('/'), 'create' => AudienceListResource\Pages\CreateRecord::route('/create'), 'edit' => AudienceListResource\Pages\EditRecord::route('/{record}/edit')];
    }
}
