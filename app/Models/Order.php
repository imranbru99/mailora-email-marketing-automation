<?php

namespace App\Models;

class Order extends TenantModel
{
    /** @return array<string,string> */
    protected function casts(): array
    {
        return [];
    }
}
