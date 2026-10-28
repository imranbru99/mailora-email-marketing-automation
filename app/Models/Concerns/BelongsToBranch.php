<?php

namespace App\Models\Concerns;

use App\Models\Branch;
use App\Models\Template;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToBranch
{
    protected static function bootBelongsToBranch(): void
    {
        static::addGlobalScope('branch', function (Builder $query) {
            if ($branch = app(TenantContext::class)->branchId) {
                $query->where(function (Builder $scope) use ($branch, $query) {
                    $scope->where($query->getModel()->qualifyColumn('branch_id'), $branch);
                    if ($query->getModel() instanceof Template) {
                        $scope->orWhereNull($query->getModel()->qualifyColumn('branch_id'));
                    }
                });
            }
        });
        static::saving(function ($model) {
            $context = app(TenantContext::class);
            if ($context->branchId) {
                if ($model->exists && (int) $model->getRawOriginal('branch_id') !== $context->branchId) {
                    throw new \LogicException('Cross-branch mutation rejected.');
                }
                $model->branch_id = $context->branchId;
            }
            if ($model->branch_id && ! Branch::where('business_id', $context->businessId)->whereKey($model->branch_id)->exists()) {
                throw new \LogicException('Invalid branch.');
            }
        });
        static::deleting(function ($model) {
            $branch = app(TenantContext::class)->branchId;
            if ($branch && (int) $model->getRawOriginal('branch_id') !== $branch) {
                throw new \LogicException('Cross-branch deletion rejected.');
            }
        });
    }
}
