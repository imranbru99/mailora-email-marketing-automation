<?php

namespace App\Filament\Resources\CampaignResource\Pages;

use App\Filament\Resources\CampaignResource;
use App\Services\Audit;

class CreateRecord extends \Filament\Resources\Pages\CreateRecord
{
    protected static string $resource = CampaignResource::class;

    protected function afterCreate(): void
    {
        Audit::record('record.created', $this->record);
    }
}
