<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property list<string> $depends_on
 */
class Module extends Model
{
    protected $guarded = [];

    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['depends_on' => 'array', 'is_core' => 'boolean', 'default_enabled' => 'boolean'];
    }
}
