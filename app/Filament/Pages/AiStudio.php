<?php

namespace App\Filament\Pages;

use App\Jobs\GenerateCopy;
use App\Models\AiGeneration;
use App\Services\ModuleRegistry;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/** @property Form $form */
class AiStudio extends Page
{
    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static string $view = 'filament.pages.ai-studio';

    /** @var array<string,mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return app(ModuleRegistry::class)->enabled('ai-studio') && (auth()->user()?->allows('templates', 'create') ?? false);
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form->schema([Select::make('type')->options(['subject' => 'Subject lines', 'copy' => 'Email copy', 'translate' => 'Translation', 'rewrite' => 'Rewrite'])->required(), Textarea::make('prompt')->label('Your brief')->required()->maxLength(2000)->rows(6)])->statePath('data');
    }

    public function generate(): void
    {
        abort_unless(static::canAccess(), 403);
        $g = AiGeneration::create($this->form->getState());
        GenerateCopy::dispatch($g->business_id, $g->branch_id, $g->id);
        Notification::make()->title('Generation queued')->success()->send();
    }

    protected function getViewData(): array
    {
        return ['generations' => AiGeneration::latest()->limit(10)->get()];
    }
}
