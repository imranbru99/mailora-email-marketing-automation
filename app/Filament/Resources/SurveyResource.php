<?php

namespace App\Filament\Resources;

use App\Models\Survey;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Tables;
use Filament\Tables\Table;

class SurveyResource extends TenantResource
{
    protected static ?string $model = Survey::class;

    public static string $module = 'surveys';

    protected static ?string $navigationGroup = 'Growth';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    public static function form(Form $form): Form
    {
        return $form->schema([TextInput::make('name')->maxLength(255)->required(), TextInput::make('question')->maxLength(255)->required(), TagsInput::make('options')->helperText('Enter each option and press Enter.'), Toggle::make('published')])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable()->sortable(), Tables\Columns\TextColumn::make('question')->sortable()->toggleable(), Tables\Columns\TextColumn::make('published')->sortable()->toggleable()])->filters([Tables\Filters\TrashedFilter::make()])->actions([Tables\Actions\ActionGroup::make([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()->requiresConfirmation(), Tables\Actions\RestoreAction::make(), Tables\Actions\Action::make('preview')->url(fn ($record) => route('survey.show', $record->public_id))->openUrlInNewTab()])])->defaultSort('created_at', 'desc');
    }

    /** @return array<string,PageRegistration> */
    public static function getPages(): array
    {
        return ['index' => SurveyResource\Pages\ManageRecords::route('/')];
    }
}
