<?php

namespace App\Models;

class FeedbackEvent extends TenantModel
{
    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['occurred_at' => 'datetime', 'processed_at' => 'datetime'];
    }
}
