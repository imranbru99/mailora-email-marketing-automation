<?php

namespace App\Filament\Resources\AudienceListResource\Pages;

use App\Filament\Resources\AudienceListResource;
use App\Services\Audit;

class EditRecord extends \Filament\Resources\Pages\EditRecord
{
    protected static string $resource = AudienceListResource::class;

    protected function afterSave(): void
    {
        Audit::record('record.updated', $this->record);
    }
}
