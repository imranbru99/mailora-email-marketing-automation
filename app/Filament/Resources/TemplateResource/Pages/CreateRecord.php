<?php

namespace App\Filament\Resources\TemplateResource\Pages;

use App\Filament\Resources\TemplateResource;
use App\Services\Audit;

class CreateRecord extends \Filament\Resources\Pages\CreateRecord
{
    protected static string $resource = TemplateResource::class;

    protected function afterCreate(): void
    {
        Audit::record('record.created', $this->record);
    }
}
