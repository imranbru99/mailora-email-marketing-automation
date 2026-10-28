<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Services\Audit;

class CreateRecord extends \Filament\Resources\Pages\CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function afterCreate(): void
    {
        Audit::record('record.created', $this->record);
    }
}
