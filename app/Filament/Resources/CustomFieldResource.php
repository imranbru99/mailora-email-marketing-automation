<?php

namespace App\Filament\Resources;

use App\Models\CustomField;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Tables;
use Filament\Tables\Table;

class CustomFieldResource extends TenantResource
{
    protected static ?string $model = CustomField::class;

    public static string $module = 'custom-fields';

    protected static ?string $navigationGroup = 'Audience';

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    public static function form(Form $form): Form
    {
        return $form->schema([TextInput::make('name')->maxLength(255)->required(), TextInput::make('key')->maxLength(255)->required(), Select::make('type')->options(['text' => 'Text', 'number' => 'Number', 'date' => 'Date', 'select' => 'Select', 'boolean' => 'Boolean', 'url' => 'URL'])->required()->default('text'), TagsInput::make('options')->helperText('Enter each option and press Enter.'), Toggle::make('required')])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable()->sortable(), Tables\Columns\TextColumn::make('key')->sortable()->toggleable(), Tables\Columns\TextColumn::make('type')->sortable()->toggleable(), Tables\Columns\TextColumn::make('required')->sortable()->toggleable()])->filters([Tables\Filters\TrashedFilter::make()])->actions([Tables\Actions\ActionGroup::make([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()->requiresConfirmation(), Tables\Actions\RestoreAction::make()])])->defaultSort('created_at', 'desc');
    }

    /** @return array<string,PageRegistration> */
    public static function getPages(): array
    {
        return ['index' => CustomFieldResource\Pages\ManageRecords::route('/')];
    }
}
