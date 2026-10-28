<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class Audit
{
    /**
     * @param  array<string,mixed>  $metadata
     */
    public static function record(string $action, ?Model $subject = null, array $metadata = []): void
    {
        AuditLog::create(['business_id' => app(TenantContext::class)->businessId, 'user_id' => auth()->id(), 'action' => $action, 'subject_type' => $subject ? $subject::class : null, 'subject_id' => $subject?->getKey(), 'metadata' => $metadata]);
    }
}
