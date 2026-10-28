<?php

namespace App\Filament\Resources;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Order;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends TenantResource
{
    protected static ?string $model = Order::class;

    public static string $module = 'orders';

    protected static ?string $navigationGroup = 'Commerce';

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    public static function form(Form $form): Form
    {
        return $form->schema([TextInput::make('number')->required(), Select::make('contact_id')->options(fn () => Contact::limit(100)->pluck('email', 'id'))->searchable()->required(), Select::make('campaign_id')->options(fn () => Campaign::pluck('name', 'id')), TextInput::make('total_minor')->numeric()->minValue(0)->required(), TextInput::make('currency')->length(3)->default('USD'), Select::make('status')->options(['paid' => 'Paid', 'pending' => 'Pending', 'refunded' => 'Refunded'])->default('paid')])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('number')->searchable()->sortable(), Tables\Columns\TextColumn::make('total_minor')->searchable()->sortable(), Tables\Columns\TextColumn::make('currency')->searchable()->sortable(), Tables\Columns\TextColumn::make('status')->searchable()->sortable()->badge(), Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true)])->filters([Tables\Filters\SelectFilter::make('status')->options(fn () => static::getModel()::query()->distinct()->pluck('status', 'status')->all())])->actions([Tables\Actions\ActionGroup::make([Tables\Actions\EditAction::make()])])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => OrderResource\Pages\ListRecords::route('/'), 'create' => OrderResource\Pages\CreateRecord::route('/create'), 'edit' => OrderResource\Pages\EditRecord::route('/{record}/edit')];
    }
}
