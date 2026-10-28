<?php

namespace App\Filament\Resources\AutomationResource\Pages;

use App\Filament\Resources\AutomationResource;
use App\Services\Audit;

class CreateRecord extends \Filament\Resources\Pages\CreateRecord
{
    protected static string $resource = AutomationResource::class;

    protected function afterCreate(): void
    {
        Audit::record('record.created', $this->record);
    }
}
