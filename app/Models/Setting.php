<?php

namespace App\Models;

use App\Services\CacheService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected $casts = [
        'value' => 'json',
    ];

    /**
     * Get a setting value by key, with optional default.
     * Results are cached for 1 hour and busted on save/delete.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            $settings = Cache::remember(CacheService::KEY_SETTINGS_ALL, CacheService::TTL_SETTINGS, function () {
                return static::all()->keyBy('key');
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Setting::get cache failed, falling back to DB.', ['error' => $e->getMessage()]);
            $settings = static::all()->keyBy('key');
        }

        return $settings->has($key) ? $settings[$key]->value : $default;
    }

    /**
     * Set (upsert) a setting value by key and bust the cache.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(CacheService::KEY_SETTINGS_ALL);
    }

    protected static function booted(): void
    {
        $bust = fn () => Cache::forget(CacheService::KEY_SETTINGS_ALL);
        static::saved($bust);
        static::deleted($bust);
    }
}

