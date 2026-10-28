<?php

namespace App\Filament\Resources\SendingIdentityResource\Pages;

use App\Filament\Resources\SendingIdentityResource;
use App\Services\Audit;

class CreateRecord extends \Filament\Resources\Pages\CreateRecord
{
    protected static string $resource = SendingIdentityResource::class;

    protected function afterCreate(): void
    {
        Audit::record('record.created', $this->record);
    }
}
