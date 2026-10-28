<?php

namespace App\Services;

use App\Jobs\PrepareCampaign;
use App\Models\Business;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\MessageLog;
use App\Models\Segment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CampaignService
{
    public function approve(Campaign $campaign): void
    {
        abort_unless(auth()->user()?->allows('campaigns', 'approve'), 403);
        DB::transaction(function () use ($campaign) {
            $locked = Campaign::lockForUpdate()->findOrFail($campaign->id);
            if (! in_array($locked->status, ['draft', 'pending approval'], true)) {
                throw ValidationException::withMessages(['campaign' => 'Only drafts may be approved.']);
            }
            $locked->update(['approved_at' => now(), 'approved_by' => auth()->id(), 'status' => 'draft']);
            Audit::record('campaign.approved', $locked);
        });
    }

    public function launch(Campaign $campaign): void
    {
        abort_unless(auth()->user()?->allows('campaigns', 'send'), 403);
        DB::transaction(function () use ($campaign) {
            $locked = Campaign::lockForUpdate()->findOrFail($campaign->id);
            if (! in_array($locked->status, ['draft', 'paused', 'scheduled'], true)) {
                throw ValidationException::withMessages(['campaign' => 'This campaign cannot be sent in its current state.']);
            }
            $errors = app(ComplianceGuard::class)->campaignErrors($locked);
            if ($errors) {
                throw ValidationException::withMessages(['campaign' => implode(' ', $errors)]);
            }
            $scheduled = $locked->scheduled_at?->isFuture();
            $locked->update(['status' => $scheduled ? 'scheduled' : 'sending']);
            PrepareCampaign::dispatch($locked->business_id, $locked->branch_id, $locked->id)->delay($scheduled ? $locked->scheduled_at : now())->afterCommit();
            Audit::record('campaign.queued', $locked);
        });
    }

    /**
     * @return Builder<Contact>
     */
    public function audience(Campaign $campaign): Builder
    {
        $query = $campaign->segment_id ? app(SegmentEngine::class)->query(Segment::findOrFail($campaign->segment_id)->conditions) : Contact::query();
        if ($campaign->audience_list_id) {
            $query->whereHas('lists', fn ($q) => $q->where('audience_lists.id', $campaign->audience_list_id));
        }

        return $query->where('status', 'subscribed')->whereNotNull('consented_at');
    }

    public function finish(Campaign $campaign): void
    {
        DB::transaction(function () use ($campaign) {
            Business::lockForUpdate()->findOrFail($campaign->business_id);
            $locked = Campaign::lockForUpdate()->find($campaign->id);
            if ($locked?->status === 'sending' && ! MessageLog::where('campaign_id', $locked->id)->whereIn('status', ['pending', 'dispatching', 'uncertain'])->exists()) {
                $locked->update(['status' => 'sent', 'sent_at' => now()]);
            }
        });
    }
}
