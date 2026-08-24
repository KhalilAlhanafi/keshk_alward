<?php

namespace App\Models;

use App\Services\CacheService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name_ar',
        'slug',
        'description',
        'sku',
        'base_price',
        'image_path',
        'is_best_seller',
        'is_active',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'base_price' => 'integer',
        'is_best_seller' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'name',
        'price',
        'primary_image_url',
        'formatted_price',
    ];

    protected static function booted(): void
    {
        $flush = fn () => CacheService::flushProductCaches();
        static::saved($flush);
        static::deleted($flush);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(ProductSize::class);
    }

    public function addons(): BelongsToMany
    {
        return $this->belongsToMany(Addon::class, 'product_addon');
    }

    /**
     * Accessor for price alias (base_price).
     */
    public function getPriceAttribute(): int
    {
        return $this->base_price ?? 0;
    }

    /**
     * Accessor for name alias (name_ar or fallback).
     */
    public function getNameAttribute(): string
    {
        return $this->name_ar ?? $this->attributes['name'] ?? 'منتج فاخر';
    }

    /**
     * Accessor for primary image URL.
     */
    public function getPrimaryImageUrlAttribute(): string
    {
        if (!empty($this->image_path)) {
            return str_starts_with($this->image_path, 'http') ? $this->image_path : asset('storage/' . $this->image_path);
        }
        return 'https://images.unsplash.com/photo-1563241527-3004b7be0ffd?auto=format&fit=crop&w=600&q=80';
    }

    /**
     * Get the formatted base price.
     */
    public function getFormattedPriceAttribute(): string
    {
        return format_money($this->base_price);
    }
}
