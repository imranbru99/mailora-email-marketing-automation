<?php

namespace App\Filament\Resources\SendingIdentityResource\Pages;

use App\Filament\Resources\SendingIdentityResource;
use App\Services\Audit;

class EditRecord extends \Filament\Resources\Pages\EditRecord
{
    protected static string $resource = SendingIdentityResource::class;

    protected function afterSave(): void
    {
        Audit::record('record.updated', $this->record);
    }
}
