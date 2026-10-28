<?php

namespace App\Filament\Resources\DealResource\Pages;

use App\Filament\Resources\DealResource;
use App\Services\Audit;

class EditRecord extends \Filament\Resources\Pages\EditRecord
{
    protected static string $resource = DealResource::class;

    protected function afterSave(): void
    {
        Audit::record('record.updated', $this->record);
    }
}
