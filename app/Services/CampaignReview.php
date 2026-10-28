<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\ReviewComment;
use Illuminate\Support\Facades\DB;

class CampaignReview
{
    public function comment(Campaign $campaign, string $body): ReviewComment
    {
        abort_unless(app(ModuleRegistry::class)->enabled('comments') && auth()->user()?->allows('comments', 'create'), 403);
        abort_unless(Campaign::whereKey($campaign->id)->exists(), 404);
        $body = trim($body);
        abort_unless(mb_strlen($body) > 0 && mb_strlen($body) <= 5000, 422);
        $comment = ReviewComment::create(['branch_id' => $campaign->branch_id, 'campaign_id' => $campaign->id, 'user_id' => auth()->id(), 'body' => $body]);
        Audit::record('campaign.comment', $campaign);

        return $comment;
    }

    public function request(Campaign $campaign): void
    {
        abort_unless(auth()->user()?->allows('campaigns', 'edit') && app(ModuleRegistry::class)->enabled('approvals'), 403);
        DB::transaction(function () use ($campaign) {
            $current = Campaign::lockForUpdate()->findOrFail($campaign->id);
            abort_unless($current->status === 'draft', 422);
            $current->update(['status' => 'pending approval', 'approved_at' => null, 'approved_by' => null]);
            Audit::record('campaign.review_requested', $current);
        });
    }

    public function reject(Campaign $campaign, string $reason): void
    {
        abort_unless(auth()->user()?->allows('campaigns', 'approve') && app(ModuleRegistry::class)->enabled('approvals'), 403);
        $reason = trim($reason);
        abort_unless($reason !== '' && mb_strlen($reason) <= 5000, 422);
        DB::transaction(function () use ($campaign, $reason) {
            $current = Campaign::lockForUpdate()->findOrFail($campaign->id);
            abort_unless($current->status === 'pending approval', 422);
            $current->update(['status' => 'draft', 'approved_at' => null, 'approved_by' => null]);
            ReviewComment::create(['branch_id' => $current->branch_id, 'campaign_id' => $current->id, 'user_id' => auth()->id(), 'body' => 'Changes requested: '.$reason]);
            Audit::record('campaign.rejected', $current);
        });
    }

    public function cancel(Campaign $campaign): void
    {
        abort_unless(auth()->user()?->allows('campaigns', 'send') && app(ModuleRegistry::class)->enabled('campaigns'), 403);
        DB::transaction(function () use ($campaign) {
            Business::lockForUpdate()->findOrFail($campaign->business_id);
            $current = Campaign::lockForUpdate()->findOrFail($campaign->id);
            abort_unless(! in_array($current->status, ['sent', 'cancelled']), 422);
            $current->update(['status' => 'cancelled']);
            Audit::record('campaign.cancelled',$current);
        });
    }
}
