<?php

namespace App\Filament\Resources\AutomationResource\Pages;

use App\Filament\Resources\AutomationResource;
use App\Services\Audit;

class EditRecord extends \Filament\Resources\Pages\EditRecord
{
    protected static string $resource = AutomationResource::class;

    protected function afterSave(): void
    {
        Audit::record('record.updated', $this->record);
    }
}
