<?php

namespace App\Models;

use Illuminate\Support\Carbon;

/** @property Carbon|null $expires_at */
class DataExport extends TenantModel
{
    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }
}
