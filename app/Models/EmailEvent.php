<?php

namespace App\Models;

class EmailEvent extends TenantModel
{
    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['is_bot' => 'boolean'];
    }
}
