<?php

namespace App\Filament\Resources;

use App\Models\Contact;
use App\Services\ContactService;
use App\Services\ModuleRegistry;
use App\Services\TenantContext;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class ContactResource extends TenantResource
{
    protected static ?string $model = Contact::class;

    public static string $module = 'contacts';

    protected static ?string $navigationGroup = 'Audience';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('email')->email()->required()->maxLength(254)->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->where('business_id', app(TenantContext::class)->businessId)),
            TextInput::make('first_name')->required()->maxLength(100), TextInput::make('last_name')->maxLength(100), TextInput::make('phone')->tel(), TextInput::make('company')->maxLength(200),
            Select::make('status')->options(['subscribed' => 'Subscribed', 'pending' => 'Pending confirmation', 'unsubscribed' => 'Unsubscribed'])->default('pending')->required(),
            Select::make('locale')->options(['en' => 'English', 'bn' => 'বাংলা', 'ar' => 'العربية'])->default('en'), TextInput::make('timezone')->default('UTC'),
            TextInput::make('source')->default('manual'), TextInput::make('score')->numeric()->default(0), TagsInput::make('tags'),
            DateTimePicker::make('consented_at')->label('Consent recorded at'), Select::make('legal_basis')->options(['consent' => 'Explicit consent', 'contract' => 'Contract', 'legitimate_interest' => 'Legitimate interest']),
            Select::make('lists')->relationship('lists', 'name')->multiple()->searchable()->preload()->visible(fn () => app(ModuleRegistry::class)->enabled('lists'))->rules([Rule::exists('audience_lists', 'id')->where(fn ($q) => $q->where('business_id', app(TenantContext::class)->businessId)->whereNull('deleted_at')->when(app(TenantContext::class)->branchId, fn ($q, $branch) => $q->where('branch_id', $branch)))]), KeyValue::make('custom_fields'), Textarea::make('notes')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('email')->searchable()->sortable(), Tables\Columns\TextColumn::make('first_name')->searchable()->sortable(), Tables\Columns\TextColumn::make('company')->searchable()->sortable(), Tables\Columns\TextColumn::make('status')->searchable()->sortable()->badge(), Tables\Columns\TextColumn::make('score')->searchable()->sortable(), Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true)])->filters([Tables\Filters\SelectFilter::make('status')->options(fn () => static::getModel()::query()->distinct()->pluck('status', 'status')->all())])->actions([Tables\Actions\ActionGroup::make([Tables\Actions\EditAction::make(), Tables\Actions\Action::make('exportProfile')->label('Export profile')->url(fn ($record) => route('contacts.profile', $record->public_id))->openUrlInNewTab()->visible(fn () => app(ModuleRegistry::class)->enabled('consent') && auth()->user()->allows('contacts', 'export')), Tables\Actions\Action::make('unsubscribe')->requiresConfirmation()->visible(fn () => static::canCreate())->action(fn ($record) => app(ContactService::class)->unsubscribe($record)), Tables\Actions\Action::make('erase')->color('danger')->requiresConfirmation()->modalDescription('Erase profile information and retain the suppression entry to prevent future marketing.')->visible(fn () => static::canCreate())->action(fn ($record) => app(ContactService::class)->erase($record))])])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ContactResource\Pages\ListRecords::route('/'), 'create' => ContactResource\Pages\CreateRecord::route('/create'), 'edit' => ContactResource\Pages\EditRecord::route('/{record}/edit')];
    }
}
