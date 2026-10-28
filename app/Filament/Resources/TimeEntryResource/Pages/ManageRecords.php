<?php

namespace App\Filament\Resources\TimeEntryResource\Pages;

use App\Filament\Resources\TimeEntryResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;

class ManageRecords extends \Filament\Resources\Pages\ManageRecords
{
    protected static string $resource = TimeEntryResource::class;

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
