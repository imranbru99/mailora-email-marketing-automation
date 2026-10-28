<?php

namespace App\Models;

use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $dns_verified_at
 * @property Carbon|null $verified_at
 */
class SendingIdentity extends TenantModel
{
    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['verified_at' => 'datetime', 'dns_verified_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        parent::booted();
        static::saving(function ($sender) {
            if ($sender->exists && ($sender->isDirty('domain') || $sender->isDirty('email'))) {
                $sender->verified_at = null;
                $sender->dns_verified_at = null;
                $sender->verification_token = null;
            }
        });
    }
}
