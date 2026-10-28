<?php

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Resources\ContactResource;
use App\Services\Audit;

class EditRecord extends \Filament\Resources\Pages\EditRecord
{
    protected static string $resource = ContactResource::class;

    protected function afterSave(): void
    {
        Audit::record('record.updated', $this->record);
    }
}
