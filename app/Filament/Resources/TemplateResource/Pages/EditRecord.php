<?php

namespace App\Filament\Resources\TemplateResource\Pages;

use App\Filament\Resources\TemplateResource;
use App\Services\Audit;

class EditRecord extends \Filament\Resources\Pages\EditRecord
{
    protected static string $resource = TemplateResource::class;

    protected function afterSave(): void
    {
        Audit::record('record.updated', $this->record);
    }
}
