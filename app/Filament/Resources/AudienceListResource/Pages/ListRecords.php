<?php

namespace App\Filament\Resources\AudienceListResource\Pages;

use App\Filament\Resources\AudienceListResource;
use Filament\Actions\CreateAction;

class ListRecords extends \Filament\Resources\Pages\ListRecords
{
    protected static string $resource = AudienceListResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
