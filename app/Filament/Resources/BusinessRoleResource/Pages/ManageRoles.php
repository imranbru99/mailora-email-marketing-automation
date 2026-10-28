<?php

namespace App\Filament\Resources\BusinessRoleResource\Pages;

use App\Filament\Resources\BusinessRoleResource;
use App\Services\TenantContext;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRoles extends ManageRecords
{
    protected static string $resource = BusinessRoleResource::class;

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->mutateFormDataUsing(fn (array $data) => [...$data, 'business_id' => app(TenantContext::class)->businessId])];
    }
}
