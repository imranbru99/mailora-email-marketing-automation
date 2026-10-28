<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Services\Audit;

class EditRecord extends \Filament\Resources\Pages\EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function afterSave(): void
    {
        Audit::record('record.updated', $this->record);
    }
}
