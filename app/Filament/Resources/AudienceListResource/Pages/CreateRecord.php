<?php

namespace App\Filament\Resources\AudienceListResource\Pages;

use App\Filament\Resources\AudienceListResource;
use App\Services\Audit;

class CreateRecord extends \Filament\Resources\Pages\CreateRecord
{
    protected static string $resource = AudienceListResource::class;

    protected function afterCreate(): void
    {
        Audit::record('record.created', $this->record);
    }
}
