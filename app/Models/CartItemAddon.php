<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItemAddon extends Model
{
    protected $fillable = [
        'cart_item_id',
        'addon_id',
        'price_snapshot',
    ];

    protected $casts = [
        'cart_item_id' => 'integer',
        'addon_id' => 'integer',
        'price_snapshot' => 'integer',
    ];

    public function cartItem(): BelongsTo
    {
        return $this->belongsTo(CartItem::class);
    }

    public function addon(): BelongsTo
    {
        return $this->belongsTo(Addon::class);
    }

    public function getFormattedPriceAttribute(): string
    {
        return format_money($this->price_snapshot);
    }
}
