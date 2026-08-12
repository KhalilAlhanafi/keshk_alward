<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CouponService
{
    /**
     * Validate a coupon code for a cart.
     *
     * @param string $code
     * @param Cart $cart
     * @param User|null $user
     * @return array
     * @throws \RuntimeException
     */
    public function validate(string $code, Cart $cart, ?User $user = null): array
    {
        $coupon = Coupon::where('code', $code)
            ->where('is_active', true)
            ->firstOrFail();

        // Check expiration
        if (now()->lt($coupon->starts_at) || now()->gt($coupon->expires_at)) {
            throw new \RuntimeException('الكود غير صالح أو منتهي الصلاحية.');
        }

        // Check usage limit
        if ($coupon->usage_limit && $coupon->usage_count >= $coupon->usage_limit) {
            throw new \RuntimeException('تم الوصول إلى الحد الأقصى لاستخدام هذا الكود.');
        }

        // Check per-user limit
        if ($user && $coupon->per_user_limit) {
            $userUsage = CouponUsage::where('coupon_id', $coupon->id)
                ->where('user_id', $user->id)
                ->count();
            if ($userUsage >= $coupon->per_user_limit) {
                throw new \RuntimeException('لقد استخدمت هذا الكود الحد الأقصى المسموح.');
            }
        }

        // Check minimum order value
        $cartTotal = app(CartTotalsService::class)->calculate($cart)['total'];
        if ($cartTotal < $coupon->minimum_order_value) {
            throw new \RuntimeException("الحد الأدنى للطلب لاستخدام هذا الكود هو {$coupon->minimum_order_value} ل.س.");
        }

        return [
            'coupon' => $coupon,
            'discount_amount' => $this->calculateDiscount($coupon, $cartTotal),
        ];
    }

    /**
     * Calculate discount amount based on coupon type.
     *
     * @param Coupon $coupon
     * @param int $cartTotal
     * @return int
     */
    protected function calculateDiscount(Coupon $coupon, int $cartTotal): int
    {
        if ($coupon->type === 'percentage') {
            $discount = ($cartTotal * $coupon->value) / 100;
            return $coupon->max_discount_amount 
                ? min($discount, $coupon->max_discount_amount) 
                : $discount;
        }

        return min($coupon->value, $cartTotal);
    }

    /**
     * Apply a coupon to an order.
     *
     * @param Coupon $coupon
     * @param Order $order
     * @param int $discountAmount
     * @return void
     */
    public function apply(Coupon $coupon, Order $order, int $discountAmount): void
    {
        DB::transaction(function () use ($coupon, $order, $discountAmount) {
            // Update order
            $order->discount_total = $discountAmount;
            $order->total -= $discountAmount;
            $order->save();

            // Record usage
            CouponUsage::create([
                'coupon_id' => $coupon->id,
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'discount_amount' => $discountAmount,
            ]);

            // Increment coupon usage count
            $coupon->increment('usage_count');
        });
    }

    /**
     * Get formatted discount amount.
     *
     * @param int $amount
     * @return string
     */
    public function formatDiscount(int $amount): string
    {
        return format_money($amount);
    }
}