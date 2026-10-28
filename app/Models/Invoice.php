<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property array<int,mixed>|null $items
 * @property Carbon|null $due_at
 */
class Invoice extends TenantModel
{
    use SoftDeletes;

    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['items' => 'array', 'due_at' => 'datetime'];
    }
}
