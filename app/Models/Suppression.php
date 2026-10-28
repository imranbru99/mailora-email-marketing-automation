<?php

namespace App\Models;

class Suppression extends TenantModel
{
    /** @return array<string,string> */
    protected function casts(): array
    {
        return [];
    }

    protected static function booted(): void
    {
        parent::booted();
        static::saving(fn ($m) => $m->email = mb_strtolower(trim($m->email)));
    }
}
