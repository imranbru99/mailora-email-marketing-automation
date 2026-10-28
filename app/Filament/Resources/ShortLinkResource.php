<?php

namespace App\Filament\Resources;

use App\Models\ShortLink;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Tables;
use Filament\Tables\Table;

class ShortLinkResource extends TenantResource
{
    protected static ?string $model = ShortLink::class;

    public static string $module = 'short-links';

    protected static ?string $navigationGroup = 'Growth';

    protected static ?string $navigationIcon = 'heroicon-o-link';

    public static function form(Form $form): Form
    {
        return $form->schema([TextInput::make('name')->maxLength(255)->required(), TextInput::make('slug')->required()->maxLength(200)->helperText('A unique public URL name within this business.'), TextInput::make('destination')->url()->required(), Toggle::make('active')])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable()->sortable(), Tables\Columns\TextColumn::make('slug')->sortable()->toggleable(), Tables\Columns\TextColumn::make('destination')->sortable()->toggleable(), Tables\Columns\TextColumn::make('clicks')->sortable()->toggleable(), Tables\Columns\TextColumn::make('active')->sortable()->toggleable()])->filters([Tables\Filters\TrashedFilter::make()])->actions([Tables\Actions\ActionGroup::make([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()->requiresConfirmation(), Tables\Actions\RestoreAction::make(), Tables\Actions\Action::make('preview')->url(fn ($record) => route('short.redirect', $record->public_id))->openUrlInNewTab()])])->defaultSort('created_at', 'desc');
    }

    /** @return array<string,PageRegistration> */
    public static function getPages(): array
    {
        return ['index' => ShortLinkResource\Pages\ManageRecords::route('/')];
    }
}
