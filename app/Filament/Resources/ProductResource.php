<?php

namespace App\Filament\Resources;

use App\Models\Product;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends TenantResource
{
    protected static ?string $model = Product::class;

    public static string $module = 'products';

    protected static ?string $navigationGroup = 'Commerce';

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    public static function form(Form $form): Form
    {
        return $form->schema([TextInput::make('name')->required(), TextInput::make('sku')->required(), Textarea::make('description'), TextInput::make('price_minor')->label('Price (minor units)')->numeric()->minValue(0)->required(), TextInput::make('currency')->length(3)->default('USD')->required(), TextInput::make('stock')->numeric()->minValue(0)->default(0)])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable()->sortable(), Tables\Columns\TextColumn::make('sku')->searchable()->sortable(), Tables\Columns\TextColumn::make('price_minor')->searchable()->sortable(), Tables\Columns\TextColumn::make('currency')->searchable()->sortable(), Tables\Columns\TextColumn::make('stock')->searchable()->sortable(), Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true)])->filters([])->actions([Tables\Actions\ActionGroup::make([Tables\Actions\EditAction::make()])])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ProductResource\Pages\ListRecords::route('/'), 'create' => ProductResource\Pages\CreateRecord::route('/create'), 'edit' => ProductResource\Pages\EditRecord::route('/{record}/edit')];
    }
}
