<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToBusiness;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

/**
 * @property int $id
 * @property string $public_id
 * @property int $business_id
 * @property int|null $branch_id
 */
abstract class TenantModel extends Model
{
    use BelongsToBranch, BelongsToBusiness;

    protected $guarded = ['id', 'business_id', 'public_id'];

    protected static function booted(): void
    {
        static::saving(function ($model) {
            $context = app(TenantContext::class);
            if ($model->exists && ((int) $model->business_id !== $context->businessId || (int) $model->getRawOriginal('business_id') !== $context->businessId)) {
                throw new \LogicException('Cross-business mutation rejected.');
            }
            foreach (['signup_form_id' => SignupForm::class, 'survey_id' => Survey::class, 'webinar_id' => Webinar::class, 'coupon_id' => Coupon::class, 'sending_identity_id' => SendingIdentity::class, 'audience_list_id' => AudienceList::class, 'segment_id' => Segment::class, 'contact_id' => Contact::class, 'campaign_id' => Campaign::class, 'automation_id' => Automation::class, 'automation_run_id' => AutomationRun::class, 'message_log_id' => MessageLog::class, 'feedback_endpoint_id' => FeedbackEndpoint::class] as $field => $class) {
                if ($model->isDirty($field) && $model->getAttribute($field) && ! $class::whereKey($model->getAttribute($field))->exists()) {
                    throw ValidationException::withMessages([$field => 'The selected record is outside this workspace.']);
                }
            }
        });
        static::deleting(function ($model) {
            if ((int) $model->getRawOriginal('business_id') !== app(TenantContext::class)->businessId) {
                throw new \LogicException('Cross-business deletion rejected.');
            }
        });
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
