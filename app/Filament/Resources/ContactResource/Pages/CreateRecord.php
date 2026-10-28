<?php

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Resources\ContactResource;
use App\Models\Contact;
use App\Services\Audit;
use App\Services\AutomationEngine;

class CreateRecord extends \Filament\Resources\Pages\CreateRecord
{
    protected static string $resource = ContactResource::class;

    protected function afterCreate(): void
    {
        Audit::record('contact.created', $this->record);
        if ($this->record instanceof Contact) {
            app(AutomationEngine::class)->enroll($this->record, 'contact.created');
        }
    }
}
