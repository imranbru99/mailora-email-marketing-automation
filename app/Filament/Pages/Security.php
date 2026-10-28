<?php

namespace App\Filament\Pages;

use App\Services\Audit;
use App\Services\Totp;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;

class Security extends Page
{
    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static string $view = 'filament.pages.security';

    public string $code = '';

    public string $password = '';

    #[Locked]
    public string $setupSecret = '';

    /** @var list<string> */
    #[Locked]
    public array $codes = [];

    public function setup(): void
    {
        $this->setupSecret = app(Totp::class)->secret();
    }

    public function enable(): void
    {
        $this->validate(['code' => ['required', 'digits:6'], 'password' => ['required', 'current_password']]);
        $step = app(Totp::class)->verify($this->setupSecret, $this->code);
        if ($step === null) {
            throw ValidationException::withMessages(['code' => 'The code does not match.']);
        }
        $this->codes = [];
        for ($i = 0; $i < 8; $i++) {
            $this->codes[] = bin2hex(random_bytes(6));
        }
        auth()->user()->forceFill(['two_factor_secret' => $this->setupSecret, 'last_totp_step' => $step, 'recovery_codes' => array_map(fn ($c) => hash('sha256', $c), $this->codes)])->save();
        session()->put('two_factor_user', auth()->id());
        $this->setupSecret = '';
        $this->password = '';
        $this->code = '';
        Audit::record('security.two_factor_enabled', auth()->user());
    }

    public function disable(): void
    {
        $this->validate(['password' => ['required', 'current_password']]);
        auth()->user()->forceFill(['two_factor_secret' => null, 'last_totp_step' => null, 'recovery_codes' => null])->save();
        $this->password = '';
        $this->codes = [];
        Audit::record('security.two_factor_disabled', auth()->user());
    }
}
