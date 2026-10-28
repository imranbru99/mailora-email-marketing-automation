<?php

namespace App\Filament\Resources\LandingPageResource\Pages;

use App\Filament\Resources\LandingPageResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;

class ManageRecords extends \Filament\Resources\Pages\ManageRecords
{
    protected static string $resource = LandingPageResource::class;

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
