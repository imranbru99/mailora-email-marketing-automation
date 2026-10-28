<?php

namespace App\Filament\Resources;

use App\Models\Branch;
use App\Models\User;
use App\Services\Audit;
use App\Services\TenantContext;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserResource extends \Filament\Resources\Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = 'Sales & Team';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Team';

    public static function canViewAny(): bool
    {
        return auth()->user()?->allows('team', 'edit') ?? false;
    }

    /** @return Builder<User> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('business_id', app(TenantContext::class)->businessId)->where('role', '!=', 'Super Admin');
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        $roles = ['Admin', 'Branch Manager', 'Marketing Manager', 'Campaign Editor', 'Content/Designer', 'Automation Specialist', 'Analyst', 'Sales Rep', 'Support Agent', 'Finance/Billing', 'Client', 'API-only'];

        return $form->schema([TextInput::make('name')->required(), TextInput::make('email')->email()->required()->unique(ignoreRecord: true), TextInput::make('password')->password()->minLength(12)->required(fn (string $operation) => $operation === 'create')->dehydrated(fn ($state) => filled($state))->helperText('Leave blank to keep the existing password.'), Select::make('role')->options(array_combine($roles, $roles))->required(), Select::make('branch_id')->options(fn () => Branch::where('business_id', app(TenantContext::class)->businessId)->pluck('name', 'id'))->helperText('A branch assignment restricts this user to that branch.'), Select::make('locale')->options(['en' => 'English', 'bn' => 'বাংলা', 'ar' => 'العربية'])->default('en')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable(), Tables\Columns\TextColumn::make('email')->searchable(), Tables\Columns\TextColumn::make('role')->badge(), Tables\Columns\TextColumn::make('branch_id')])->actions([Tables\Actions\EditAction::make()->visible(fn ($record) => $record->id !== auth()->id() && $record->role !== 'Business Owner')->using(function (User $record, array $data) {
            abort_if($record->id === auth()->id() || $record->role === 'Business Owner', 403);
            self::validateBranch($data);
            $record->forceFill($data)->save();
            Audit::record('team.updated', $record);

            return $record;
        })]);
    }

    /** @param array<string,mixed> $data */
    public static function validateBranch(array $data): void
    {
        if (! empty($data['branch_id'])) {
            abort_unless(Branch::where('business_id', app(TenantContext::class)->businessId)->whereKey($data['branch_id'])->exists(), 403);
        }
    }

    /** @return array<string,PageRegistration> */
    public static function getPages(): array
    {
        return ['index' => UserResource\Pages\ManageUsers::route('/')];
    }
}
