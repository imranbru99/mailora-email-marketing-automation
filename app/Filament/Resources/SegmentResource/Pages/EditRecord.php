<?php

namespace App\Filament\Resources\SegmentResource\Pages;

use App\Filament\Resources\SegmentResource;
use App\Services\Audit;

class EditRecord extends \Filament\Resources\Pages\EditRecord
{
    protected static string $resource = SegmentResource::class;

    protected function afterSave(): void
    {
        Audit::record('record.updated', $this->record);
    }
}
