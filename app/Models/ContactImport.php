<?php

namespace App\Models;

/**
 * @property list<string>|null $errors
 */
class ContactImport extends TenantModel
{
    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['errors' => 'array'];
    }
}
