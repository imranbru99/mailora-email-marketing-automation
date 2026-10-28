<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CouponService
{
    public function redeem(string $code, Contact $contact): CouponRedemption
    {
        abort_unless(app(ModuleRegistry::class)->enabled('coupons'), 403);

        return DB::transaction(function () use ($code, $contact) {
            $coupon = Coupon::where('code', $code)->lockForUpdate()->firstOrFail();
            if ($existing = CouponRedemption::where('coupon_id', $coupon->id)->where('contact_id', $contact->id)->first()) {
                return $existing;
            }
            if ($coupon->expires_at?->isPast() || ($coupon->max_uses > 0 && $coupon->uses >= $coupon->max_uses)) {
                throw ValidationException::withMessages(['code' => 'This coupon is no longer available.']);
            }
            $redemption = CouponRedemption::create(['coupon_id' => $coupon->id, 'contact_id' => $contact->id]);
            $coupon->increment('uses');
            Audit::record('coupon.redeemed', $coupon);

            return $redemption;
        });
    }
}
