<?php

namespace App\Filament\Resources\SegmentResource\Pages;

use App\Filament\Resources\SegmentResource;
use App\Services\Audit;

class CreateRecord extends \Filament\Resources\Pages\CreateRecord
{
    protected static string $resource = SegmentResource::class;

    protected function afterCreate(): void
    {
        Audit::record('record.created', $this->record);
    }
}
