<?php

namespace App\Jobs;

use App\Contracts\ReportPdf;
use App\Models\Business;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\DataExport;
use App\Models\User;
use App\Services\ModuleRegistry;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class GenerateExport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 180;

    public function __construct(public int $businessId, public ?int $branchId, public int $exportId) {}

    public function handle(): void
    {
        app(TenantContext::class)->run($this->businessId, $this->branchId, function () {
            $export = DataExport::find($this->exportId);
            if (! $export || $export->status !== 'queued') {
                return;
            }
            $module = $export->type === 'contacts' ? 'contacts' : 'campaign-reports';
            $user = User::find($export->user_id);
            if (! $user || (int) $user->business_id !== $this->businessId || ($user->branch_id && (int) $user->branch_id !== $this->branchId) || ! $user->allows($module, 'export') || ! app(ModuleRegistry::class)->enabled($module) || ! app(ModuleRegistry::class)->enabled('exports')) {
                $export->update(['status' => 'cancelled', 'error' => 'Access or module settings changed.']);

                return;
            }
            if (! DataExport::whereKey($export->id)->where('status', 'queued')->update(['status' => 'processing'])) {
                return;
            }
            $extension = $export->type === 'campaign-pdf' ? 'pdf' : 'csv';
            $path = 'exports/'.$this->businessId.'/'.$export->public_id.'.'.$extension;
            try {
                if ($extension === 'pdf') {
                    $campaign = Campaign::findOrFail($export->record_id);
                    $response = app(ReportPdf::class)->download('reports.campaign', ['campaign' => $campaign, 'business' => Business::findOrFail($this->businessId)], 'campaign.pdf');
                    $content = $response->getContent();
                    if ($content === false) {
                        throw new \RuntimeException('PDF output unavailable.');
                    }
                    Storage::disk('local')->put($path, $content);
                } else {
                    $stream = fopen('php://temp/maxmemory:2097152', 'w+');
                    if ($stream === false) {
                        throw new \RuntimeException('Unable to create export stream.');
                    }
                    try {
                        $columns = $export->type === 'contacts' ? ['email', 'first_name', 'last_name', 'company', 'status', 'locale', 'source', 'consented_at'] : ['name', 'subject', 'status', 'sent_count', 'open_count', 'click_count', 'bounce_count', 'sent_at'];
                        fputcsv($stream, $columns, ',', '"', '');
                        $query = $export->type === 'contacts' ? Contact::query() : Campaign::query();
                        foreach ($query->select(['id', ...$columns])->lazyById(500) as $record) {
                            $row = [];
                            foreach ($columns as $column) {
                                $v = (string) ($record->getAttribute($column) ?? '');
                                $row[] = preg_match('/^[=+@\-\t\r\n]/', $v) ? "'".$v : $v;
                            }fputcsv($stream, $row, ',', '"', '');
                        }
                        rewind($stream);
                        if (! Storage::disk('local')->put($path, $stream)) {
                            throw new \RuntimeException('Unable to save export.');
                        }
                    } finally {
                        fclose($stream);
                    }
                }
                $export->update(['status' => 'ready', 'path' => $path, 'expires_at' => now()->addDay()]);
            } catch (\Throwable $e) {
                Storage::disk('local')->delete($path);
                $export->update(['status' => 'failed', 'error' => 'Export could not be generated. Please contact your administrator.']);
                report($e);
            }
        });
    }
}
