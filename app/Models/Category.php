<?php

namespace App\Models;

use App\Services\CacheService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name_ar',
        'slug',
        'image_path',
        'parent_id',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Append computed attributes to JSON/array serialization.
     * This ensures image_url is included in json_encode($cat) in Blade views.
     */
    protected $appends = [
        'name',
        'image_url',
    ];

    protected static function booted(): void
    {
        $flush = fn () => CacheService::flushCategoryCaches();
        static::saved($flush);
        static::deleted($flush);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getNameAttribute(): string
    {
        return (string) $this->name_ar;
    }

    public function getImageUrlAttribute(): ?string
    {
        if (empty($this->image_path)) return null;

        // Base64 data URI or external URL — use as-is (legacy fallback)
        if (str_starts_with($this->image_path, 'data:') || str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }

        // Direct static storage asset URL for instant web server serving
        return asset('storage/' . ltrim($this->image_path, '/'));
    }
}
