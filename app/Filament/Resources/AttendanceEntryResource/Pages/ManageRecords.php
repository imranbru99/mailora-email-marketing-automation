<?php

namespace App\Filament\Resources\AttendanceEntryResource\Pages;

use App\Filament\Resources\AttendanceEntryResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;

class ManageRecords extends \Filament\Resources\Pages\ManageRecords
{
    protected static string $resource = AttendanceEntryResource::class;

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
