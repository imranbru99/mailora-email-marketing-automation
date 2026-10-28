<?php

namespace App\Models\Concerns;

use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait BelongsToBusiness
{
    protected static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope('business', function (Builder $query) {
            $context = app(TenantContext::class);
            $context->businessId ? $query->where($query->getModel()->qualifyColumn('business_id'), $context->businessId) : $query->whereRaw('1 = 0');
        });
        static::creating(function ($model) {
            $context = app(TenantContext::class);
            if (! $context->businessId) {
                throw new \LogicException('A business context is required.');
            }
            $model->business_id = $context->businessId;
            $model->public_id ??= (string) Str::ulid();
        });
    }
}
