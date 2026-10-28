<?php

namespace App\Filament\Resources;

use App\Models\SendingIdentity;
use App\Services\DomainVerifier;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;

class SendingIdentityResource extends TenantResource
{
    protected static ?string $model = SendingIdentity::class;

    public static string $module = 'identities';

    protected static ?string $navigationGroup = 'Delivery';

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    public static function form(Form $form): Form
    {
        return $form->schema([TextInput::make('name')->required(), TextInput::make('email')->email()->required(), TextInput::make('domain')->required(), Textarea::make('postal_address')->required()->columnSpanFull()])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable()->sortable(), Tables\Columns\TextColumn::make('email')->searchable()->sortable(), Tables\Columns\TextColumn::make('domain')->searchable()->sortable(), Tables\Columns\TextColumn::make('verified_at')->searchable()->sortable()->dateTime(), Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true)])->filters([])->actions([Tables\Actions\ActionGroup::make([Tables\Actions\EditAction::make(), Tables\Actions\Action::make('dns')->label('Verify DNS')->visible(fn () => auth()->user()->allows('identities', 'edit'))->modalHeading('Verify sender domain')->modalDescription(fn ($record) => 'Create a TXT record at _mailora.'.$record->domain.' with value: '.app(DomainVerifier::class)->challenge($record).'. Also configure SPF and DMARC, then run the check.')->requiresConfirmation()->action(function ($record) {
            $result = app(DomainVerifier::class)->check($record);
            Notification::make()->title(! in_array(false, $result, true) ? 'Domain verified' : 'DNS verification incomplete')->body(collect($result)->map(fn ($v, $k) => $k.': '.($v ? 'pass' : 'missing'))->implode(' · '))->send();
        }), Tables\Actions\Action::make('sandbox_verify')->label('Verify for sandbox')->requiresConfirmation()->visible(fn () => config('platform.sandbox') && auth()->user()->allows('identities', 'edit'))->action(fn ($record) => $record->update(['verified_at' => now()]))])])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => SendingIdentityResource\Pages\ListRecords::route('/'), 'create' => SendingIdentityResource\Pages\CreateRecord::route('/create'), 'edit' => SendingIdentityResource\Pages\EditRecord::route('/{record}/edit')];
    }
}
