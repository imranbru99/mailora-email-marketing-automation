<?php

namespace App\Filament\Resources\SignupFormResource\Pages;

use App\Filament\Resources\SignupFormResource;
use App\Services\Audit;

class EditRecord extends \Filament\Resources\Pages\EditRecord
{
    protected static string $resource = SignupFormResource::class;

    protected function afterSave(): void
    {
        Audit::record('record.updated', $this->record);
    }
}
