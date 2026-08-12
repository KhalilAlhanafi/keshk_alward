<?php

namespace App\Models;

use App\Enums\SizeKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSize extends Model
{
    protected $fillable = [
        'product_id',
        'size_key',
        'label_ar',
        'price',
        'stock',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'size_key' => SizeKey::class,
        'price' => 'integer',
        'stock' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the formatted price for this size.
     */
    public function getFormattedPriceAttribute(): string
    {
        return format_money($this->price);
    }
}
