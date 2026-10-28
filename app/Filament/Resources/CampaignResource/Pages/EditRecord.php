<?php

namespace App\Filament\Resources\CampaignResource\Pages;

use App\Filament\Resources\CampaignResource;
use App\Services\Audit;

class EditRecord extends \Filament\Resources\Pages\EditRecord
{
    protected static string $resource = CampaignResource::class;

    protected function afterSave(): void
    {
        Audit::record('record.updated', $this->record);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['approved_at'] = null;
        $data['approved_by'] = null;

        return $data;
    }
}
