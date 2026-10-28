<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends TenantModel
{
    use SoftDeletes;

    /** @return array<string,string> */
    protected function casts(): array
    {
        return [];
    }
}
