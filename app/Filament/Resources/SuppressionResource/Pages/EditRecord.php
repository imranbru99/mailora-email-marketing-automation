<?php

namespace App\Filament\Resources\SuppressionResource\Pages;

use App\Filament\Resources\SuppressionResource;
use App\Services\Audit;

class EditRecord extends \Filament\Resources\Pages\EditRecord
{
    protected static string $resource = SuppressionResource::class;

    protected function afterSave(): void
    {
        Audit::record('record.updated', $this->record);
    }
}
