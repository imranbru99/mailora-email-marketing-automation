<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Services\Audit;
use App\Services\TenantContext;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->using(function (array $data) {
            UserResource::validateBranch($data);
            $user = new User;
            $user->forceFill([...$data, 'business_id' => app(TenantContext::class)->businessId])->save();
            Audit::record('team.created', $user);

            return $user;
        })];
    }
}
