<?php

namespace App\Filament\Resources;

use App\Models\BusinessRole;
use App\Models\Module;
use App\Services\TenantContext;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BusinessRoleResource extends \Filament\Resources\Resource
{
    protected static ?string $model = BusinessRole::class;

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationIcon = 'heroicon-o-key';

    public static function canViewAny(): bool
    {
        return in_array(auth()->user()?->role, ['Super Admin', 'Business Owner'], true);
    }

    /** @return Builder<BusinessRole> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('business_id', app(TenantContext::class)->businessId);
    }

    public static function form(Form $form): Form
    {
        $roles = ['Branch Manager', 'Marketing Manager', 'Campaign Editor', 'Content/Designer', 'Automation Specialist', 'Analyst', 'Sales Rep', 'Support Agent', 'Finance/Billing', 'Client', 'API-only'];
        $permissions = [];
        foreach (Module::all() as $m) {
            foreach (['view', 'create', 'edit', 'delete', 'export', 'send', 'approve', 'publish'] as $action) {
                $permissions[$m->key.':'.$action] = $m->name.' · '.$action;
            }
        }

        return $form->schema([Select::make('name')->options(array_combine($roles, $roles))->required()->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->where('business_id', app(TenantContext::class)->businessId)), CheckboxList::make('permissions')->options($permissions)->searchable()->columns(2)->columnSpanFull()]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable(), Tables\Columns\TextColumn::make('updated_at')->dateTime()])->actions([Tables\Actions\EditAction::make()]);
    }

    /** @return array<string,PageRegistration> */
    public static function getPages(): array
    {
        return ['index' => BusinessRoleResource\Pages\ManageRoles::route('/')];
    }
}
