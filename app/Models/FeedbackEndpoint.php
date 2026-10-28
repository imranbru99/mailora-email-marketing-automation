<?php

namespace App\Models;

/** @property string $secret */
class FeedbackEndpoint extends TenantModel
{
    protected $hidden = ['secret'];

    protected static function booted(): void
    {
        parent::booted();
        static::creating(fn (self $endpoint) => $endpoint->secret = bin2hex(random_bytes(32)));
    }

    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['secret' => 'encrypted', 'active' => 'boolean'];
    }
}
