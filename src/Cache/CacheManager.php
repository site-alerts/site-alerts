<?php

namespace SiteAlerts\Cache;

use SiteAlerts\Abstracts\AbstractSingleton;
use SiteAlerts\Utils\DateTimeUtils;
use SiteAlerts\Utils\Logger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CacheManager
 *
 * Professional caching system for WordPress plugins.
 * Supports transients, object cache, and file-based caching.
 *
 * @package SiteAlerts\Cache
 * @version 1.0.0
 */
class CacheManager extends AbstractSingleton
{
    /**
     * Cache group name
     *
     * @var string
     */
    private string $group = 'site_alerts_cache';

    /**
     * Default cache expiration (1 hour)
     *
     * @var int
     */
    private int $defaultExpiration = HOUR_IN_SECONDS;

    /**
     * Whether object cache is available
     *
     * @var bool
     */
    private ?bool $objectCacheAvailable;

    /**
     * Cache statistics
     *
     * @var array
     */
    private array $stats = [
        'hits'   => 0,
        'misses' => 0,
        'writes' => 0,
    ];

    /**
     * Local runtime cache
     *
     * @var array
     */
    private array $localCache = [];

    /**
     * Initialize the cache manager.
     */
    protected function __construct()
    {
        parent::__construct();
        $this->objectCacheAvailable = wp_using_ext_object_cache();

        // Add non-persistent groups if using object cache
        if ($this->objectCacheAvailable) {
            wp_cache_add_non_persistent_groups([$this->group . '_temp']);
        }
    }

    /**
     * Register hooks.
     *
     * @return void
     */
    public function register(): void
    {
        // Clear cache on certain events
        add_action('switch_theme', [$this, 'flush']);
        add_action('upgrader_process_complete', [$this, 'flush']);

        // Admin bar cache clear
        if (is_admin()) {
            add_action('admin_bar_menu', [$this, 'addAdminBarMenu'], 100);
            add_action('admin_init', [$this, 'handleCacheClear']);
        }
    }

    /**
     * Set the cache group.
     *
     * @param string $group Cache group name.
     * @return self
     */
    public function setGroup(string $group): self
    {
        $this->group = $group;
        return $this;
    }

    /**
     * Get a cached value.
     *
     * @param string $key Cache key.
     * @param mixed $default Default value if not found.
     * @param string|null $group Optional cache group.
     * @return mixed
     */
    public function get(string $key, $default = null, ?string $group = null)
    {
        $group   = $group ?? $this->group;
        $fullKey = $this->buildKey($key, $group);

        // Check local cache first
        if (isset($this->localCache[$fullKey])) {
            $this->stats['hits']++;
            return $this->localCache[$fullKey];
        }

        // Try object cache
        if ($this->objectCacheAvailable) {
            $value = wp_cache_get($key, $group, false, $found);
            if ($found) {
                $this->stats['hits']++;
                $this->localCache[$fullKey] = $value;
                return $value;
            }
        }

        // Fall back to transients
        $transientValue = get_transient($fullKey);
        if ($transientValue !== false) {
            $this->stats['hits']++;
            $this->localCache[$fullKey] = $transientValue;
            return $transientValue;
        }

        $this->stats['misses']++;
        return $default;
    }

    /**
     * Set a cached value.
     *
     * @param string $key Cache key.
     * @param mixed $value Value to cache.
     * @param int|null $expiration Expiration in seconds.
     * @param string|null $group Optional cache group.
     * @return bool
     */
    public function set(string $key, $value, ?int $expiration = null, ?string $group = null): bool
    {
        $group      = $group ?? $this->group;
        $expiration = $expiration ?? $this->defaultExpiration;
        $fullKey    = $this->buildKey($key, $group);

        // Store in local cache
        $this->localCache[$fullKey] = $value;

        // Store in object cache
        if ($this->objectCacheAvailable) {
            wp_cache_set($key, $value, $group, $expiration);
        }

        // Store in transients for persistence
        $result = set_transient($fullKey, $value, $expiration);

        if ($result) {
            $this->stats['writes']++;
        }

        return $result;
    }

    /**
     * Delete a cached value.
     *
     * @param string $key Cache key.
     * @param string|null $group Optional cache group.
     * @return bool
     */
    public function delete(string $key, ?string $group = null): bool
    {
        $group   = $group ?? $this->group;
        $fullKey = $this->buildKey($key, $group);

        // Remove from local cache
        unset($this->localCache[$fullKey]);

        // Remove from object cache
        if ($this->objectCacheAvailable) {
            wp_cache_delete($key, $group);
        }

        // Remove from transients
        return delete_transient($fullKey);
    }

    /**
     * Check if a cache key exists.
     *
     * @param string $key Cache key.
     * @param string|null $group Optional cache group.
     * @return bool
     */
    public function has(string $key, ?string $group = null): bool
    {
        $group   = $group ?? $this->group;
        $fullKey = $this->buildKey($key, $group);

        // Check local cache
        if (isset($this->localCache[$fullKey])) {
            return true;
        }

        // Check object cache
        if ($this->objectCacheAvailable) {
            wp_cache_get($key, $group, false, $found);
            if ($found) {
                return true;
            }
        }

        // Check transients
        return get_transient($fullKey) !== false;
    }

    /**
     * Get or set a cached value using a callback.
     *
     * @param string $key Cache key.
     * @param callable $callback Callback to generate value.
     * @param int|null $expiration Expiration in seconds.
     * @param string|null $group Optional cache group.
     * @return mixed
     */
    public function remember(string $key, callable $callback, ?int $expiration = null, ?string $group = null)
    {
        $value = $this->get($key, null, $group);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();

        if ($value !== null) {
            $this->set($key, $value, $expiration, $group);
        }

        return $value;
    }

    /**
     * Get or set a cached value, storing forever.
     *
     * @param string $key Cache key.
     * @param callable $callback Callback to generate value.
     * @param string|null $group Optional cache group.
     * @return mixed
     */
    public function rememberForever(string $key, callable $callback, ?string $group = null)
    {
        return $this->remember($key, $callback, 0, $group);
    }

    /**
     * Increment a cached integer value.
     *
     * @param string $key Cache key.
     * @param int $amount Amount to increment.
     * @param int|null $expiration Expiration in seconds.
     * @param string|null $group Optional cache group.
     * @return int|false
     */
    public function increment(string $key, int $amount = 1, ?int $expiration = null, ?string $group = null)
    {
        $value = $this->get($key, 0, $group);

        if (!is_numeric($value)) {
            return false;
        }

        $newValue = (int)$value + $amount;
        $this->set($key, $newValue, $expiration, $group);

        return $newValue;
    }

    /**
     * Decrement a cached integer value.
     *
     * @param string $key Cache key.
     * @param int $amount Amount to decrement.
     * @param int|null $expiration Expiration in seconds.
     * @param string|null $group Optional cache group.
     * @return int|false
     */
    public function decrement(string $key, int $amount = 1, ?int $expiration = null, ?string $group = null)
    {
        return $this->increment($key, -$amount, $expiration, $group);
    }

    /**
     * Get multiple cached values.
     *
     * @param array $keys Cache keys.
     * @param string|null $group Optional cache group.
     * @return array
     */
    public function getMultiple(array $keys, ?string $group = null): array
    {
        $results = [];

        foreach ($keys as $key) {
            $results[$key] = $this->get($key, null, $group);
        }

        return $results;
    }

    /**
     * Set multiple cached values.
     *
     * @param array $values Key-value pairs.
     * @param int|null $expiration Expiration in seconds.
     * @param string|null $group Optional cache group.
     * @return bool
     */
    public function setMultiple(array $values, ?int $expiration = null, ?string $group = null): bool
    {
        $success = true;

        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $expiration, $group)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Delete multiple cached values.
     *
     * @param array $keys Cache keys.
     * @param string|null $group Optional cache group.
     * @return bool
     */
    public function deleteMultiple(array $keys, ?string $group = null): bool
    {
        $success = true;

        foreach ($keys as $key) {
            if (!$this->delete($key, $group)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Flush all plugin caches.
     *
     * @return bool
     */
    public function flush(): bool
    {
        global $wpdb;

        // Clear local cache
        $this->localCache = [];

        // Clear object cache group
        if ($this->objectCacheAvailable && function_exists('wp_cache_flush_group')) {
            wp_cache_flush_group($this->group);
        }

        // Clear transients with our prefix
        $prefix        = '_transient_' . $this->group;
        $timeoutPrefix = '_transient_timeout_' . $this->group;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Clearing transients requires direct query
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                $prefix . '%',
                $timeoutPrefix . '%'
            )
        );

        Logger::info('Plugin cache flushed');

        /**
         * Action fired after cache is flushed.
         */
        do_action('site_alerts_cache_flushed');

        return true;
    }

    /**
     * Flush expired transients.
     *
     * @return int Number of transients deleted.
     */
    public function flushExpired(): int
    {
        global $wpdb;

        $time = DateTimeUtils::timestamp();

        // Get expired transients
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Optimizing transients requires direct query
        $expired = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options}
                WHERE option_name LIKE %s
                AND option_value < %d",
                '_transient_timeout_' . $this->group . '%',
                $time
            )
        );

        $count = 0;

        foreach ($expired as $transient) {
            $key = str_replace('_transient_timeout_', '', $transient);
            delete_transient($key);
            $count++;
        }

        if ($count > 0) {
            Logger::debug('Expired transients cleared', ['count' => $count]);
        }

        return $count;
    }

    /**
     * Add cache clear option to admin bar.
     *
     * @param \WP_Admin_Bar $adminBar Admin bar instance.
     * @return void
     */
    public function addAdminBarMenu(\WP_Admin_Bar $adminBar): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $adminBar->add_node([
            'id'    => 'sa-clear-cache',
            'title' => __('Clear Plugin Cache', 'site-alerts'),
            'href'  => wp_nonce_url(
                admin_url('admin.php?action=sa_clear_cache'),
                'sa_clear_cache'
            ),
            'meta'  => [
                'title' => __('Clear the plugin cache', 'site-alerts'),
            ],
        ]);
    }

    /**
     * Handle cache clear request.
     *
     * @return void
     */
    public function handleCacheClear(): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified below
        if (!isset($_GET['action']) || $_GET['action'] !== 'sa_clear_cache') {
            return;
        }

        if (!check_admin_referer('sa_clear_cache')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $this->flush();

        wp_safe_redirect(wp_get_referer() ?: admin_url());
        exit;
    }

    /**
     * Get cache statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        return array_merge($this->stats, [
            'object_cache' => $this->objectCacheAvailable,
            'local_items'  => count($this->localCache),
        ]);
    }

    /**
     * Build a full cache key.
     *
     * @param string $key Cache key.
     * @param string $group Cache group.
     * @return string
     */
    private function buildKey(string $key, string $group): string
    {
        return $group . '_' . $key;
    }

    /**
     * Generate a cache key from arguments.
     *
     * @param string $prefix Key prefix.
     * @param mixed ...$args Arguments to hash.
     * @return string
     */
    public static function makeKey(string $prefix, ...$args): string
    {
        $hash = md5(serialize($args));
        return $prefix . '_' . $hash;
    }

    /**
     * Check if object cache is available.
     *
     * @return bool
     */
    public function isObjectCacheAvailable(): bool
    {
        return $this->objectCacheAvailable;
    }

    /**
     * Set default expiration time.
     *
     * @param int $seconds Expiration in seconds.
     * @return self
     */
    public function setDefaultExpiration(int $seconds): self
    {
        $this->defaultExpiration = $seconds;
        return $this;
    }

    /**
     * Get the default expiration time.
     *
     * @return int
     */
    public function getDefaultExpiration(): int
    {
        return $this->defaultExpiration;
    }
}
