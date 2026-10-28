<?php

namespace App\Filament\Pages;

use App\Services\Audit;
use App\Services\ModuleRegistry;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/** @property Form $form */
class ApiTokens extends Page
{
    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static string $view = 'filament.pages.api-tokens';

    /** @var array<string,mixed>|null */
    public ?array $data = [];

    public ?string $issuedToken = null;

    public static function canAccess(): bool
    {
        return app(ModuleRegistry::class)->enabled('api') && (auth()->user()?->allows('settings', 'edit') ?? false);
    }

    public function mount(): void
    {
        $this->form->fill(['days' => 30, 'abilities' => ['contacts:read']]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([TextInput::make('name')->required()->maxLength(100), Select::make('days')->options([7 => '7 days', 30 => '30 days', 90 => '90 days'])->required(), CheckboxList::make('abilities')->options(['contacts:read' => 'Read contacts', 'contacts:write' => 'Create pending contacts', 'events:write' => 'Submit automation events'])->required()->minItems(1), TextInput::make('password')->password()->required()->currentPassword()])->statePath('data');
    }

    public function issue(): void
    {
        abort_unless(static::canAccess(), 403);
        $data = $this->form->getState();
        if (! Hash::check($data['password'], auth()->user()->password)) {
            throw ValidationException::withMessages(['data.password' => 'Your password is incorrect.']);
        }
        $abilities = array_values(array_intersect($data['abilities'], ['contacts:read', 'contacts:write', 'events:write']));
        $days = (int) $data['days'];
        abort_unless(in_array($days, [7, 30, 90]) && count($abilities) > 0, 422);
        $this->issuedToken = auth()->user()->createToken($data['name'], $abilities, now()->addDays($days))->plainTextToken;
        Audit::record('api.token_created');
        $this->form->fill(['days' => 30, 'abilities' => ['contacts:read']]);
    }

    public function revoke(int $id): void
    {
        abort_unless(static::canAccess(), 403);
        auth()->user()->tokens()->whereKey($id)->delete();
        Audit::record('api.token_revoked');
    }

    protected function getViewData(): array
    {
        return ['tokens' => auth()->user()->tokens()->latest()->get()];
    }
}
