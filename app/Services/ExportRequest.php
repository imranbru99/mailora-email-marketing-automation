<?php

namespace App\Services;

use App\Jobs\GenerateExport;
use App\Models\Campaign;
use App\Models\DataExport;

class ExportRequest
{
    public function create(string $type, ?int $recordId = null): DataExport
    {
        abort_unless(in_array($type, ['contacts', 'campaigns', 'campaign-pdf']), 422);
        $module = $type === 'contacts' ? 'contacts' : 'campaign-reports';
        abort_unless(app(ModuleRegistry::class)->enabled('exports') && app(ModuleRegistry::class)->enabled($module) && auth()->user()->allows($module, 'export'), 403);
        if ($type === 'campaign-pdf') {
            Campaign::findOrFail($recordId);
        }
        $record = DataExport::create(['user_id' => auth()->id(), 'type' => $type, 'record_id' => $recordId]);
        GenerateExport::dispatch($record->business_id, $record->branch_id, $record->id)->afterCommit();
        Audit::record('export.requested', $record);

        return $record;
    }
}
