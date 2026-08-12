<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'minimum_order_value',
        'max_discount_amount',
        'starts_at',
        'expires_at',
        'usage_limit',
        'usage_count',
        'per_user_limit',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'minimum_order_value' => 'integer',
        'max_discount_amount' => 'integer',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'per_user_limit' => 'integer',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * Check if coupon is currently valid.
     */
    public function isValid(): bool
    {
        return $this->is_active &&
               now()->gte($this->starts_at) &&
               now()->lte($this->expires_at) &&
               (!$this->usage_limit || $this->usage_count < $this->usage_limit);
    }

    /**
     * Check if user can use this coupon.
     */
    public function canBeUsedBy(?User $user = null): bool
    {
        if (!$user) {
            return true; // Guest users can use coupon
        }

        $userUsageCount = $this->usages()->where('user_id', $user->id)->count();
        return $userUsageCount < $this->per_user_limit;
    }
}
