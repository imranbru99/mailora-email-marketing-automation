<?php

namespace App\Jobs;

use App\Models\AiGeneration;
use App\Services\AiService;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateCopy implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $businessId, public ?int $branchId, public int $generationId) {}

    public function handle(): void
    {
        app(TenantContext::class)->run($this->businessId, $this->branchId, function () {
            if ($g = AiGeneration::find($this->generationId)) {
                app(AiService::class)->execute($g);
            }
        });
    }
}
