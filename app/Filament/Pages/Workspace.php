<?php

namespace App\Filament\Pages;

use App\Models\Business;
use App\Services\Audit;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/** @property Form $form */
class Workspace extends Page
{
    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static string $view = 'filament.pages.workspace';

    /** @var array<string,mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->allows('settings', 'edit') ?? false;
    }

    public function mount(): void
    {
        $b = Business::findOrFail(auth()->user()->business_id);
        $this->form->fill(['name' => $b->name, 'settings' => $b->settings]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([TextInput::make('name')->required(), Section::make('Business defaults')->schema([Select::make('settings.timezone')->options(array_combine(timezone_identifiers_list(), timezone_identifiers_list()))->searchable()->required(), Select::make('settings.locale')->options(['en' => 'English', 'bn' => 'বাংলা', 'ar' => 'العربية'])->required(), TextInput::make('settings.currency')->length(3)->required(), TextInput::make('settings.postal_address')->required(), TextInput::make('settings.send_per_minute')->label('Maximum sends per minute')->numeric()->integer()->minValue(1)->maxValue(10000)->default(120), Toggle::make('settings.ai_enabled')->label('Enable configured AI provider'), Select::make('settings.ai_provider')->options(['openai' => 'OpenAI', 'anthropic' => 'Anthropic', 'gemini' => 'Gemini'])->default('openai'), TextInput::make('settings.ai_model')->label('AI model')->maxLength(100)->helperText('Provider credentials are configured by your server administrator.'), TextInput::make('settings.ai_token_limit')->numeric()->integer()->minValue(0)->maxValue(10000000)->default(10000)])->columns(2)])->statePath('data');
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);
        $state = $this->form->getState();
        $b = Business::findOrFail(auth()->user()->business_id);
        $state['settings'] = array_replace($b->settings ?? [], $state['settings'] ?? []);
        $b->update($state);
        Audit::record('settings.updated', $b);
        Notification::make()->title('Workspace saved')->success()->send();
    }
}
