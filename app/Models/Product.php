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
        'arrangement_details',
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
        'gallery_urls',
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
     * Accessor for all gallery image URLs of this product.
     */
    public function getGalleryUrlsAttribute(): array
    {
        if (empty($this->image_path)) {
            return ['https://images.unsplash.com/photo-1563241527-3004b7be0ffd?auto=format&fit=crop&w=600&q=80'];
        }

        $raw = $this->image_path;

        if (str_starts_with($raw, '[') || str_starts_with($raw, '{')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                // If it is a list of images [ ... ]
                if (array_is_list($decoded)) {
                    $urls = [];
                    foreach ($decoded as $item) {
                        if (is_string($item)) {
                            $urls[] = str_starts_with($item, 'http') ? $item : asset('storage/' . $item);
                        } elseif (is_array($item)) {
                            $p = $item['medium'] ?? $item['original'] ?? $item['thumbnail'] ?? $item['large'] ?? null;
                            if ($p) {
                                $urls[] = str_starts_with($p, 'http') ? $p : asset('storage/' . $p);
                            }
                        }
                    }
                    if (!empty($urls)) {
                        return $urls;
                    }
                } else {
                    // Single image variant map: {"original": "...", "medium": "..."}
                    $p = $decoded['medium'] ?? $decoded['original'] ?? $decoded['thumbnail'] ?? $decoded['large'] ?? null;
                    if ($p) {
                        return [str_starts_with($p, 'http') ? $p : asset('storage/' . $p)];
                    }
                }
            }
        }

        return [str_starts_with($raw, 'http') ? $raw : asset('storage/' . $raw)];
    }

    /**
     * Accessor for primary image URL.
     */
    public function getPrimaryImageUrlAttribute(): string
    {
        $gallery = $this->gallery_urls;
        return $gallery[0] ?? 'https://images.unsplash.com/photo-1563241527-3004b7be0ffd?auto=format&fit=crop&w=600&q=80';
    }

    /**
     * Accessor for arrangement details lines / points.
     */
    public function getArrangementPointsAttribute(): array
    {
        if (!empty($this->arrangement_details)) {
            $lines = preg_split('/\r\n|\r|\n/', $this->arrangement_details);
            $cleanLines = array_values(array_filter(array_map('trim', $lines)));
            if (!empty($cleanLines)) {
                return $cleanLines;
            }
        }

        return [
            'مجموعة ورود وأغصان طبيعية طازجة منتقاة يدوياً بعناية فائقة.',
            'تغليف قماشي/ورقي فاخر بتوقيع كشك الورد مع شريط ستان متناسق.',
            'كرت إهداء أنيق مجاني جاهز لكتابة رسالتك الخاصة.',
        ];
    }

    /**
     * Get the formatted base price.
     */
    public function getFormattedPriceAttribute(): string
    {
        return format_money($this->base_price);
    }
}
