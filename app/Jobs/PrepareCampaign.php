<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\MessageLog;
use App\Services\CampaignService;
use App\Services\ComplianceGuard;
use App\Services\ModuleRegistry;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class PrepareCampaign implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $businessId, public ?int $branchId, public int $campaignId) {}

    public function handle(): void
    {
        app(TenantContext::class)->run($this->businessId, $this->branchId, function () {
            // Commit the complete recipient snapshot before any delivery worker can finish it.
            // The business lock uses the same lock order as SendMessage and quota updates.
            $campaign = DB::transaction(function (): ?Campaign {
                Business::lockForUpdate()->findOrFail($this->businessId);
                $campaign = Campaign::lockForUpdate()->find($this->campaignId);
                if (! $campaign || ! app(ModuleRegistry::class)->enabled('campaigns') || ! in_array($campaign->status, ['scheduled', 'sending', 'preparing'], true)) {
                    return null;
                }
                if ($campaign->status === 'scheduled' && $campaign->scheduled_at?->isFuture()) {
                    return null;
                }
                if (app(ComplianceGuard::class)->campaignErrors($campaign)) {
                    $campaign->update(['status' => 'paused']);

                    return null;
                }
                // A delivery retry must not expand an audience that was already snapshotted.
                if ($campaign->status !== 'sending' || ! MessageLog::where('campaign_id', $campaign->id)->exists()) {
                    $campaign->update(['status' => 'preparing']);
                    app(CampaignService::class)->audience($campaign)->chunkById(500, function ($contacts) use ($campaign) {
                        foreach ($contacts as $contact) {
                            MessageLog::firstOrCreate(['campaign_id' => $campaign->id, 'contact_id' => $contact->id], ['branch_id' => $campaign->branch_id]);
                        }
                    });
                }
                $campaign->update(['status' => 'sending']);

                return $campaign;
            }, 3);
            if (! $campaign) {
                return;
            }
            MessageLog::where('campaign_id', $campaign->id)->where('status', 'pending')->chunkById(500, function ($messages) {
                foreach ($messages as $message) {
                    SendMessage::dispatch($this->businessId, $this->branchId, $message->id);
                }
            });
            app(CampaignService::class)->finish($campaign);
        });
    }
}
