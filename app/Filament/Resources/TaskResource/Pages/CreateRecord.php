<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Services\Audit;

class CreateRecord extends \Filament\Resources\Pages\CreateRecord
{
    protected static string $resource = TaskResource::class;

    protected function afterCreate(): void
    {
        Audit::record('record.created', $this->record);
    }
}
