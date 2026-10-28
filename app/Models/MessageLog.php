<?php

namespace App\Models;

/** @property array<string,string>|null $headers */
class MessageLog extends TenantModel
{
    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['headers' => 'array', 'delivered_at' => 'datetime', 'bounced_at' => 'datetime', 'complained_at' => 'datetime', 'sent_at' => 'datetime', 'opened_at' => 'datetime', 'clicked_at' => 'datetime'];
    }
}
