<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property list<array{type:string,data?:array<string,mixed>}>|null $blocks
 */
class Template extends TenantModel
{
    use SoftDeletes;

    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['blocks' => 'array'];
    }
}
