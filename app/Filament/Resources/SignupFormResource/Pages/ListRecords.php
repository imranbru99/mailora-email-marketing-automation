<?php

namespace App\Filament\Resources\SignupFormResource\Pages;

use App\Filament\Resources\SignupFormResource;
use Filament\Actions\CreateAction;

class ListRecords extends \Filament\Resources\Pages\ListRecords
{
    protected static string $resource = SignupFormResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
