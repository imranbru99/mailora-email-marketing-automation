<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class SignupForm extends TenantModel
{
    use SoftDeletes;

    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['published' => 'boolean', 'double_opt_in' => 'boolean'];
    }
}
