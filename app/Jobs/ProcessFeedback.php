<?php

namespace App\Jobs;

use App\Models\FeedbackEvent;
use App\Services\FeedbackProcessor;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessFeedback implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public int $businessId, public ?int $branchId, public int $eventId) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 30, 120, 300];
    }

    public function handle(): void
    {
        app(TenantContext::class)->run($this->businessId, $this->branchId, fn () => app(FeedbackProcessor::class)->process($this->eventId));
    }

    public function failed(?\Throwable $exception): void
    {
        app(TenantContext::class)->run($this->businessId, $this->branchId, fn () => FeedbackEvent::whereKey($this->eventId)->where('status', 'pending')->update(['status' => 'failed']));
    }
}
