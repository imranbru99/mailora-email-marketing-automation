<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends TenantModel
{
    use SoftDeletes;

    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['due_at' => 'datetime'];
    }
}
