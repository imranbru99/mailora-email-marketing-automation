<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property array<string,mixed>|null $settings
 */
class Branch extends Model
{
    protected $guarded = [];

    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['settings' => 'array'];
    }
}
