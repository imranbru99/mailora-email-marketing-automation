<?php

namespace App\Models;

/**
 * @property int $business_id
 * @property int|null $branch_id
 */
class AiGeneration extends TenantModel
{
    /** @return array<string,string> */
    protected function casts(): array
    {
        return [];
    }
}
