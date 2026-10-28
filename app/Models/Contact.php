<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $paused_until
 * @property list<string>|null $tags
 * @property array<string,mixed>|null $custom_fields
 * @property Carbon|null $consented_at
 */
class Contact extends TenantModel
{
    use SoftDeletes;

    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['tags' => 'array', 'custom_fields' => 'array', 'consented_at' => 'datetime', 'paused_until' => 'datetime'];
    }

    protected static function booted(): void
    {
        parent::booted();
        static::saving(fn ($m) => $m->email = mb_strtolower(trim($m->email)));
        static::creating(fn ($m) => $m->unsubscribe_token ??= bin2hex(random_bytes(32)));
    }

    /** @return BelongsToMany<AudienceList, $this> */
    public function lists(): BelongsToMany
    {
        return $this->belongsToMany(AudienceList::class);
    }
}
