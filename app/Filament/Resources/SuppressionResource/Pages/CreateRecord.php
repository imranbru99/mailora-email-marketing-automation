<?php

namespace App\Filament\Resources\SuppressionResource\Pages;

use App\Filament\Resources\SuppressionResource;
use App\Services\Audit;

class CreateRecord extends \Filament\Resources\Pages\CreateRecord
{
    protected static string $resource = SuppressionResource::class;

    protected function afterCreate(): void
    {
        Audit::record('record.created', $this->record);
    }
}
