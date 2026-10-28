<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** @property list<string> $permissions */
class BusinessRole extends Model
{
    protected $guarded = ['id'];

    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['permissions' => 'array'];
    }
}
