<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Services\Audit;

class EditRecord extends \Filament\Resources\Pages\EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function afterSave(): void
    {
        Audit::record('record.updated', $this->record);
    }
}
