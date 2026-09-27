<?php

namespace App\Models;

use App\Services\CacheService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HeroSlide extends Model
{
    protected $fillable = [
        'image_path',
        'title',
        'subtitle',
        'button_text',
        'button_link',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'image_url',
    ];

    protected static function booted(): void
    {
        $flush = fn () => CacheService::flushHeroSlideCaches();
        static::saved($flush);
        static::deleted($flush);
    }

    /**
     * Scope query to only active slides ordered by sort_order and id.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }

    /**
     * Get accessible image URL with fallback to asset storage or raw external URL.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (empty($this->image_path)) {
            return null;
        }

        if (str_starts_with($this->image_path, 'data:') || 
            str_starts_with($this->image_path, 'http://') || 
            str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }

        return asset('storage/' . ltrim($this->image_path, '/'));
    }
}
