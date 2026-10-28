<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property array<string,mixed> $conditions
 */
class Segment extends TenantModel
{
    use SoftDeletes;

    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['conditions' => 'array'];
    }
}
