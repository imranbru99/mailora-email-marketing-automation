<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property string|null $two_factor_secret
 * @property list<string>|null $recovery_codes
 * @property int|null $last_totp_step
 */
class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, Notifiable;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'recovery_codes'];

    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'two_factor_secret' => 'encrypted', 'recovery_codes' => 'array'];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->business_id !== null && $this->role !== 'API-only';
    }

    public function allows(string $module, string $action = 'view'): bool
    {
        if (in_array($this->role, ['Super Admin', 'Business Owner', 'Admin'], true)) {
            return true;
        }
        $configured = BusinessRole::where('business_id', $this->business_id)->where('name', $this->role)->first();
        if ($configured) {
            return in_array($module.':'.$action, $configured->permissions, true);
        }
        if (in_array($module, ['settings', 'modules', 'team'], true)) {
            return false;
        }
        if ($action === 'view') {
            return true;
        }
        if (in_array($this->role, ['Analyst', 'Client', 'API-only'], true)) {
            return false;
        }
        if ($action === 'approve') {
            return in_array($this->role, ['Marketing Manager', 'Branch Manager'], true);
        }
        if ($action === 'send') {
            return in_array($this->role, ['Marketing Manager', 'Branch Manager'], true);
        }
        $scopes = [
            'Branch Manager' => ['*'], 'Marketing Manager' => ['*'],
            'Campaign Editor' => ['campaigns', 'templates'], 'Content/Designer' => ['templates'],
            'Automation Specialist' => ['automation', 'segments'], 'Sales Rep' => ['contacts', 'crm', 'tasks'],
            'Support Agent' => ['contacts', 'tasks'], 'Finance/Billing' => ['orders'],
        ];

        return in_array('*', $scopes[$this->role] ?? [], true) || in_array($module, $scopes[$this->role] ?? [], true);
    }
}
