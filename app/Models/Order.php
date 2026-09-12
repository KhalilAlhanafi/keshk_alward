<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Services\CacheService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CouponUsage;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'idempotency_key',
        'user_id',
        'recipient_name',
        'recipient_phone',
        'delivery_area_id',
        'delivery_address',
        'delivery_date',
        'delivery_time_slot',
        'card_message',
        'subtotal',
        'delivery_fee',
        'total',
        'discount_total',
        'status',
        'payment_method',
        'payment_status',
        'payment_proof',
        'payment_verified_at',
        'verified_by',
        'rejection_reason',
        'transaction_number',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'delivery_area_id' => 'integer',
        'verified_by' => 'integer',
        'delivery_date' => 'date',
        'payment_verified_at' => 'datetime',
        'subtotal' => 'integer',
        'delivery_fee' => 'integer',
        'total' => 'integer',
        'discount_total' => 'integer',
        'status' => OrderStatus::class,
        'payment_method' => PaymentMethod::class,
        'payment_status' => PaymentStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function deliveryArea(): BelongsTo
    {
        return $this->belongsTo(DeliveryArea::class, 'delivery_area_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function couponUsages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * Get the formatted total price.
     */
    public function getFormattedTotalAttribute(): string
    {
        return format_money($this->total);
    }

    /**
     * Get the formatted subtotal price.
     */
    public function getFormattedSubtotalAttribute(): string
    {
        return format_money($this->subtotal);
    }

    /**
     * Get the formatted delivery fee.
     */
    public function getFormattedDeliveryFeeAttribute(): string
    {
        return format_money($this->delivery_fee);
    }

    /**
     * Alias accessor for status attribute.
     */
    public function getOrderStatusAttribute(): ?OrderStatus
    {
        return $this->status;
    }

    protected static function booted(): void
    {
        $flush = fn () => CacheService::flushOrderCaches();
        static::saved($flush);
        static::deleted($flush);
    }
}
