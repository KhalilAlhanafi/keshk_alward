<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name_snapshot',
        'size_label_snapshot',
        'unit_price',
        'quantity',
        'message',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'product_id' => 'integer',
        'unit_price' => 'integer',
        'quantity' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function addons(): HasMany
    {
        return $this->hasMany(OrderItemAddon::class);
    }

    public function getLineTotalAttribute(): int
    {
        return $this->unit_price * $this->quantity;
    }

    public function getFormattedUnitPriceAttribute(): string
    {
        return format_money($this->unit_price);
    }

    public function getFormattedPriceAttribute(): string
    {
        return format_money($this->unit_price);
    }

    public function getFormattedLineTotalAttribute(): string
    {
        return format_money($this->line_total);
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return format_money($this->line_total);
    }
}
