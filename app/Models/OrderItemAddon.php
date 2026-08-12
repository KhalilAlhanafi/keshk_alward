<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemAddon extends Model
{
    protected $fillable = [
        'order_item_id',
        'addon_name_snapshot',
        'price_snapshot',
    ];

    protected $casts = [
        'order_item_id' => 'integer',
        'price_snapshot' => 'integer',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function getFormattedPriceAttribute(): string
    {
        return format_money($this->price_snapshot);
    }
}
