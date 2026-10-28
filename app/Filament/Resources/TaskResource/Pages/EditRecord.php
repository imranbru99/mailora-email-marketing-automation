<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Services\Audit;

class EditRecord extends \Filament\Resources\Pages\EditRecord
{
    protected static string $resource = TaskResource::class;

    protected function afterSave(): void
    {
        Audit::record('record.updated', $this->record);
    }
}
