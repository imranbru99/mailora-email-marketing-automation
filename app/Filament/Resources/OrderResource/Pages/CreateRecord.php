<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Services\Audit;

class CreateRecord extends \Filament\Resources\Pages\CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function afterCreate(): void
    {
        Audit::record('record.created', $this->record);
    }
}
