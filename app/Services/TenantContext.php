<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Business;
use Closure;

final class TenantContext
{
    public ?int $businessId = null;

    public ?int $branchId = null;

    public function set(int $businessId, ?int $branchId = null): void
    {
        if ($branchId !== null && ! Branch::where('business_id', $businessId)->whereKey($branchId)->exists()) {
            abort(403);
        }
        $this->businessId = $businessId;
        $this->branchId = $branchId;
    }

    public function run(int $businessId, ?int $branchId, Closure $callback): mixed
    {
        $previous = [$this->businessId, $this->branchId];
        try {
            $this->set($businessId, $branchId);

            return $callback();
        } finally {
            [$this->businessId, $this->branchId] = $previous;
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function settings(): array
    {
        $business = Business::find($this->businessId);
        $branch = $this->branchId ? Branch::find($this->branchId) : null;

        return array_replace($business->settings ?? [], $branch->settings ?? []);
    }
}
