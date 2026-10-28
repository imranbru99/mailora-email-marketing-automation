<?php

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Pages\Exports;
use App\Filament\Resources\ContactResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;

class ListRecords extends \Filament\Resources\Pages\ListRecords
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make(), Action::make('export')->label('Export audience')->url('/admin/exports')->visible(fn () => Exports::canAccess() && auth()->user()->allows('contacts', 'export'))];
    }
}
