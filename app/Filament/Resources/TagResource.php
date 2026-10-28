<?php

namespace App\Filament\Resources;

use App\Models\Tag;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Tables;
use Filament\Tables\Table;

class TagResource extends TenantResource
{
    protected static ?string $model = Tag::class;

    public static string $module = 'tags';

    protected static ?string $navigationGroup = 'Audience';

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    public static function form(Form $form): Form
    {
        return $form->schema([TextInput::make('name')->maxLength(255)->required(), TextInput::make('color')->maxLength(255)])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable()->sortable(), Tables\Columns\TextColumn::make('color')->sortable()->toggleable()])->filters([Tables\Filters\TrashedFilter::make()])->actions([Tables\Actions\ActionGroup::make([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()->requiresConfirmation(), Tables\Actions\RestoreAction::make()])])->defaultSort('created_at', 'desc');
    }

    /** @return array<string,PageRegistration> */
    public static function getPages(): array
    {
        return ['index' => TagResource\Pages\ManageRecords::route('/')];
    }
}
