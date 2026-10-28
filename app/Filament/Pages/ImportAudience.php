<?php

namespace App\Filament\Pages;

use App\Jobs\ImportContacts;
use App\Models\ContactImport;
use App\Services\ModuleRegistry;
use App\Services\TenantContext;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/** @property Form $form */
class ImportAudience extends Page
{
    protected static ?string $navigationGroup = 'Audience';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static string $view = 'filament.pages.import-audience';

    /** @var array<string,mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return app(ModuleRegistry::class)->enabled('imports') && (auth()->user()?->allows('contacts', 'create') ?? false);
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form->schema([FileUpload::make('path')->label('CSV file')->disk('local')->directory(fn () => 'imports/'.app(TenantContext::class)->businessId)->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])->maxSize(10240)->required()])->statePath('data');
    }

    public function upload(): void
    {
        abort_unless(static::canAccess(), 403);
        $state = $this->form->getState();
        abort_unless(str_starts_with($state['path'], 'imports/'.app(TenantContext::class)->businessId.'/') && ! str_contains($state['path'], '..'), 422);
        $import = ContactImport::create($state);
        ImportContacts::dispatch($import->business_id, $import->branch_id, $import->id);
        $this->form->fill();
        Notification::make()->title('Import queued')->success()->send();
    }

    protected function getViewData(): array
    {
        return ['imports' => ContactImport::latest()->limit(15)->get()];
    }
}
