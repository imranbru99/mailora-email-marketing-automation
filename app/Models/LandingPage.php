<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property bool $published
 */
class LandingPage extends TenantModel
{
    use SoftDeletes;

    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['published' => 'boolean'];
    }
}
