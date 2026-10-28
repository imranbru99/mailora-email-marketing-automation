<?php

namespace App\Filament\Resources;

use App\Models\Invoice;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceResource extends TenantResource
{
    protected static ?string $model = Invoice::class;

    public static string $module = 'invoices';

    protected static ?string $navigationGroup = 'Billing';

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';

    public static function form(Form $form): Form
    {
        return $form->schema([TextInput::make('name')->maxLength(255)->required(), TextInput::make('customer_name')->maxLength(255)->required(), TextInput::make('customer_email')->email(), Repeater::make('items')->schema([TextInput::make('description')->required(), TextInput::make('quantity')->numeric()->minValue(1)->required(), TextInput::make('unit_price_minor')->numeric()->minValue(0)->required()])->columns(3)->columnSpanFull()->required(), TextInput::make('currency')->length(3)->default('USD'), TextInput::make('tax_percent')->numeric()->minValue(0)->default(0)->maxValue(100), Select::make('status')->options(['draft' => 'Draft', 'issued' => 'Issued', 'paid' => 'Paid', 'void' => 'Void'])->required()->default('draft'), DateTimePicker::make('due_at')->required()])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable()->sortable(), Tables\Columns\TextColumn::make('customer_name')->sortable()->toggleable(), Tables\Columns\TextColumn::make('customer_email')->sortable()->toggleable(), Tables\Columns\TextColumn::make('currency')->sortable()->toggleable(), Tables\Columns\TextColumn::make('tax_percent')->sortable()->toggleable(), Tables\Columns\TextColumn::make('status')->sortable()->badge()->toggleable(), Tables\Columns\TextColumn::make('due_at')->sortable()->dateTime()->toggleable()])->filters([Tables\Filters\TrashedFilter::make()])->actions([Tables\Actions\ActionGroup::make([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()->requiresConfirmation(), Tables\Actions\RestoreAction::make(), Tables\Actions\Action::make('pdf')->label('PDF')->url(fn ($record) => route('invoice.pdf', $record->public_id))->openUrlInNewTab()])])->defaultSort('created_at', 'desc');
    }

    /** @return array<string,PageRegistration> */
    public static function getPages(): array
    {
        return ['index' => InvoiceResource\Pages\ManageRecords::route('/')];
    }
}
