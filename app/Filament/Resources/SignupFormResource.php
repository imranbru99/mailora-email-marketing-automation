<?php

namespace App\Filament\Resources;

use App\Models\AudienceList;
use App\Models\SignupForm;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class SignupFormResource extends TenantResource
{
    protected static ?string $model = SignupForm::class;

    public static string $module = 'forms';

    protected static ?string $navigationGroup = 'Growth';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function form(Form $form): Form
    {
        return $form->schema([TextInput::make('name')->required(), TextInput::make('headline')->required(), Textarea::make('description'), TextInput::make('button_label')->required()->default('Subscribe'), Toggle::make('published'), Toggle::make('double_opt_in')->default(true), Select::make('audience_list_id')->options(fn () => AudienceList::pluck('name', 'id'))])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable()->sortable(), Tables\Columns\TextColumn::make('headline')->searchable()->sortable(), Tables\Columns\TextColumn::make('published')->searchable()->sortable(), Tables\Columns\TextColumn::make('double_opt_in')->searchable()->sortable(), Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true)])->filters([])->actions([Tables\Actions\ActionGroup::make([Tables\Actions\EditAction::make(), Tables\Actions\Action::make('open')->label('Open form')->url(fn ($record) => route('forms.show', $record->public_id))->openUrlInNewTab()])])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => SignupFormResource\Pages\ListRecords::route('/'), 'create' => SignupFormResource\Pages\CreateRecord::route('/create'), 'edit' => SignupFormResource\Pages\EditRecord::route('/{record}/edit')];
    }
}
