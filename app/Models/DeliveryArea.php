<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryArea extends Model
{
    protected $fillable = [
        'city_ar',
        'area_ar',
        'delivery_fee',
        'is_active',
    ];

    protected $casts = [
        'delivery_fee' => 'integer',
        'is_active' => 'boolean',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get formatted delivery fee.
     */
    public function getFormattedDeliveryFeeAttribute(): string
    {
        return format_money($this->delivery_fee);
    }
}
