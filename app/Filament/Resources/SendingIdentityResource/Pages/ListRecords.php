<?php

namespace App\Filament\Resources\SendingIdentityResource\Pages;

use App\Filament\Resources\SendingIdentityResource;
use Filament\Actions\CreateAction;

class ListRecords extends \Filament\Resources\Pages\ListRecords
{
    protected static string $resource = SendingIdentityResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
