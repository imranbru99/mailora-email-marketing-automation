<?php

namespace App\Filament\Resources\DealResource\Pages;

use App\Filament\Resources\DealResource;
use App\Services\Audit;

class CreateRecord extends \Filament\Resources\Pages\CreateRecord
{
    protected static string $resource = DealResource::class;

    protected function afterCreate(): void
    {
        Audit::record('record.created', $this->record);
    }
}
