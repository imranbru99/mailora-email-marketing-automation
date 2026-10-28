<?php

namespace App\Support\Integrations;

use App\Contracts\PerformanceInspector;
use Drutogoti\Models\Suggestion;
use Illuminate\Support\Facades\Schema;

class DrutogotiAdapter implements PerformanceInspector
{
    /**
     * @return list<array<string,mixed>>
     */
    public function suggestions(): array
    {
        if (! config('drutogoti.enabled') || ! Schema::hasTable('drutogoti_suggestions')) {
            return [];
        }

        return Suggestion::where('is_resolved', false)->limit(20)->get(['category', 'severity', 'title'])->toArray();
    }
}
