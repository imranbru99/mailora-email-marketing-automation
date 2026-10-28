<?php

namespace App\Filament\Pages;

use App\Models\Module;
use App\Services\ModuleRegistry;
use App\Services\PresetService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;

class Modules extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationGroup = 'Settings';

    protected static string $view = 'filament.pages.modules';

    public string $search = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->allows('modules', 'edit') ?? false;
    }

    public function toggle(string $key): void
    {
        abort_unless(static::canAccess(), 403);
        try {
            $registry = app(ModuleRegistry::class);
            $registry->toggle($key, ! $registry->enabled($key));
            Notification::make()->title('Module configuration saved')->success()->send();
        } catch (ValidationException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    protected function getViewData(): array
    {
        return ['modules' => Module::where('name', 'like', '%'.$this->search.'%')->orderBy('id')->get()->groupBy('category')];
    }

    public string $preset = 'Custom';

    public function applyPreset(): void
    {
        abort_unless(static::canAccess(), 403);
        abort_unless(in_array($this->preset, config('platform.presets'), true), 422);
        app(PresetService::class)->apply($this->preset);
        Notification::make()->title('Preset applied')->success()->send();
    }
}
