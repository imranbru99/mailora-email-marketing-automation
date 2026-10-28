<?php

namespace App\Filament\Resources\SignupFormResource\Pages;

use App\Filament\Resources\SignupFormResource;
use App\Services\Audit;

class CreateRecord extends \Filament\Resources\Pages\CreateRecord
{
    protected static string $resource = SignupFormResource::class;

    protected function afterCreate(): void
    {
        Audit::record('record.created', $this->record);
    }
}
