<?php

namespace App\Models;

/** @property list<string> $findings */
class EmailCheck extends TenantModel
{
    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['findings' => 'array'];
    }
}
