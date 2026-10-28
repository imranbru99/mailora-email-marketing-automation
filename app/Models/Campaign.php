<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $scheduled_at
 * @property Carbon|null $approved_at
 * @property Carbon|null $sent_at
 */
class Campaign extends TenantModel
{
    use SoftDeletes;

    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'approved_at' => 'datetime', 'sent_at' => 'datetime', 'tracking' => 'boolean'];
    }

    /** @return BelongsTo<SendingIdentity, $this> */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(SendingIdentity::class, 'sending_identity_id');
    }
}
