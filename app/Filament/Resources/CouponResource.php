<?php

namespace App\Filament\Resources;

use App\Models\Coupon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Tables;
use Filament\Tables\Table;

class CouponResource extends TenantResource
{
    protected static ?string $model = Coupon::class;

    public static string $module = 'coupons';

    protected static ?string $navigationGroup = 'Commerce';

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    public static function form(Form $form): Form
    {
        return $form->schema([TextInput::make('name')->maxLength(255)->required(), TextInput::make('code')->maxLength(255)->required(), TextInput::make('discount_percent')->numeric()->minValue(0)->default(0)->maxValue(100), DateTimePicker::make('expires_at')->required(), TextInput::make('max_uses')->numeric()->minValue(0)->default(0)])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable()->sortable(), Tables\Columns\TextColumn::make('code')->sortable()->toggleable(), Tables\Columns\TextColumn::make('discount_percent')->sortable()->toggleable(), Tables\Columns\TextColumn::make('expires_at')->sortable()->dateTime()->toggleable(), Tables\Columns\TextColumn::make('max_uses')->sortable()->toggleable(), Tables\Columns\TextColumn::make('uses')->sortable()->toggleable()])->filters([Tables\Filters\TrashedFilter::make()])->actions([Tables\Actions\ActionGroup::make([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()->requiresConfirmation(), Tables\Actions\RestoreAction::make()])])->defaultSort('created_at', 'desc');
    }

    /** @return array<string,PageRegistration> */
    public static function getPages(): array
    {
        return ['index' => CouponResource\Pages\ManageRecords::route('/')];
    }
}
