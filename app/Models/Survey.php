<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property array<int,mixed>|null $options
 * @property bool $published
 */
class Survey extends TenantModel
{
    use SoftDeletes;

    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['options' => 'array', 'published' => 'boolean'];
    }
}
