<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $worked_on
 */
class TimeEntry extends TenantModel
{
    use SoftDeletes;

    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['worked_on' => 'date'];
    }
}
