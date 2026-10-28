<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use App\Services\PresetService;
use App\Services\TenantContext;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Register extends \Filament\Pages\Auth\Register
{
    /** @return array<int|string,string|Form> */
    protected function getForms(): array
    {
        return ['form' => $this->makeForm()->schema([$this->getNameFormComponent(), $this->getEmailFormComponent(), TextInput::make('business_name')->required()->maxLength(150), Select::make('preset')->options(array_combine(config('platform.presets'), config('platform.presets')))->required(), $this->getPasswordFormComponent(), $this->getPasswordConfirmationFormComponent()])->statePath('data')];
    }

    /** @param array<string,mixed> $data */
    protected function handleRegistration(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $business = Business::create(['name' => $data['business_name'], 'preset' => $data['preset'], 'settings' => ['timezone' => 'UTC', 'locale' => 'en', 'currency' => 'USD', 'postal_address' => ''], 'monthly_email_limit' => 1000]);
            Branch::create(['business_id' => $business->id, 'name' => 'Head Office']);
            $user = new User;
            $user->forceFill(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'], 'business_id' => $business->id, 'role' => 'Business Owner'])->save();
            app(TenantContext::class)->run($business->id, null, fn () => app(PresetService::class)->apply($data['preset']));

            return $user;
        });
    }
}
