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
     * Get the formatted base price.
     */
    public function getFormattedPriceAttribute(): string
    {
        return format_money($this->base_price);
    }
}
