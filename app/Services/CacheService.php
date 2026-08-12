<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * CacheService — canonical cache key builder and flush helpers.
 *
 * All cache keys in the application are defined here as constants or
 * generated through static methods, making it impossible to have typos
 * or orphaned keys scattered across controllers.
 *
 * KEY MAP (with TTLs):
 * ─────────────────────────────────────────────────────────────────
 * | Key pattern                              | TTL   | Busted by           |
 * |------------------------------------------|-------|---------------------|
 * | categories_active                        | 24 h  | Category save/delete|
 * | categories_all                           | 24 h  | Category save/delete|
 * | best_sellers                             |  1 h  | Product save/delete |
 * | catalog:{hash}                           | 15 m  | Product save/delete |
 * | admin_dashboard_stats                    |  5 m  | Order/Product save  |
 * | settings_all                             |  1 h  | Setting save/delete |
 * ─────────────────────────────────────────────────────────────────
 */
class CacheService
{
    // ── TTL constants ───────────────────────────────────────────────

    public const TTL_CATALOG = 15 * 60;          // 15 minutes
    public const TTL_BEST_SELLERS = 60 * 60;     // 1 hour
    public const TTL_CATEGORIES = 24 * 60 * 60;  // 24 hours
    public const TTL_DASHBOARD = 5 * 60;         // 5 minutes
    public const TTL_SETTINGS = 60 * 60;         // 1 hour

    // ── Static key names ────────────────────────────────────────────

    public const KEY_CATEGORIES_ACTIVE = 'categories_active';
    public const KEY_CATEGORIES_ALL = 'categories_all';
    public const KEY_BEST_SELLERS = 'best_sellers';
    public const KEY_DASHBOARD_STATS = 'admin_dashboard_stats';
    public const KEY_SETTINGS_ALL = 'settings_all';

    /**
     * Build a stable, deterministic catalog cache key from request parameters.
     *
     * All params are sorted alphabetically so
     * ?sort_by=newest&category_id=2 ≡ ?category_id=2&sort_by=newest
     */
    public static function catalogKey(array $params): string
    {
        $filtered = array_filter($params, fn($v) => $v !== null && $v !== '');
        ksort($filtered);
        return 'catalog:' . md5(json_encode($filtered));
    }

    /**
     * Remember catalog results and register the cache key so it can be
     * bulk-flushed later via flushCatalog().
     */
    public static function rememberCatalog(array $params, \Closure $callback): mixed
    {
        $key = self::catalogKey($params);
        $ttl = self::TTL_CATALOG;

        // Register this key in a meta-list so we can flush all catalog keys at once.
        // We store the registry in the same cache store as an array.
        $registry = Cache::get('catalog_key_registry', []);
        if (!in_array($key, $registry)) {
            $registry[] = $key;
            Cache::put('catalog_key_registry', $registry, self::TTL_CATALOG + 60);
        }

        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Flush every catalog page cache key.
     * Called from Product observer on save/delete.
     */
    public static function flushCatalog(): void
    {
        $registry = Cache::get('catalog_key_registry', []);
        foreach ($registry as $key) {
            Cache::forget($key);
        }
        Cache::forget('catalog_key_registry');
    }

    /**
     * Flush all product-related cache keys.
     */
    public static function flushProductCaches(): void
    {
        Cache::forget(self::KEY_BEST_SELLERS);
        Cache::forget(self::KEY_CATEGORIES_ACTIVE);
        Cache::forget(self::KEY_CATEGORIES_ALL);
        Cache::forget(self::KEY_DASHBOARD_STATS);
        self::flushCatalog();
    }

    /**
     * Flush all order-related stats caches.
     */
    public static function flushOrderCaches(): void
    {
        Cache::forget(self::KEY_DASHBOARD_STATS);
    }

    /**
     * Flush all category-related caches.
     */
    public static function flushCategoryCaches(): void
    {
        Cache::forget(self::KEY_CATEGORIES_ACTIVE);
        Cache::forget(self::KEY_CATEGORIES_ALL);
        Cache::forget(self::KEY_DASHBOARD_STATS);
    }
}
