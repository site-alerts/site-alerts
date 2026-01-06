<?php

namespace SiteAlerts\Cache;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class FragmentCache
 *
 * Cache HTML fragments/partials for improved template performance.
 *
 * @package SiteAlerts\Cache
 * @version 1.0.0
 */
class FragmentCache
{
    /**
     * Cache group for fragments
     */
    public const GROUP = 'site_alerts_fragment';

    /**
     * Default expiration (30 minutes)
     */
    public const DEFAULT_EXPIRATION = 1800;

    /**
     * Cache manager instance
     *
     * @var CacheManager
     */
    private CacheManager $cache;

    /**
     * Active fragment key being captured
     *
     * @var string|null
     */
    private ?string $activeFragment = null;

    /**
     * Output buffer started flag
     *
     * @var bool
     */
    private bool $bufferStarted = false;

    /**
     * FragmentCache constructor.
     */
    public function __construct()
    {
        $this->cache = CacheManager::getInstance();
    }

    /**
     * Start capturing a fragment.
     *
     * If the fragment exists in cache, outputs it and returns false.
     * Otherwise starts output buffering and returns true.
     *
     * @param string $key Fragment key.
     * @param int|null $expiration Cache expiration in seconds.
     * @param array $vary Variables that affect the fragment content.
     * @return bool True if capturing started, false if cached content was output.
     */
    public function start(string $key, ?int $expiration = null, array $vary = []): bool
    {
        $fullKey = $this->buildKey($key, $vary);

        // Check cache
        $cached = $this->cache->get($fullKey, null, self::GROUP);

        if ($cached !== null) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Cached HTML output
            echo $cached;
            return false;
        }

        // Start capturing
        $this->activeFragment = $fullKey;
        $this->bufferStarted  = true;
        ob_start();

        return true;
    }

    /**
     * End capturing and cache the fragment.
     *
     * @param int|null $expiration Cache expiration in seconds (overrides start value).
     * @return string The captured content.
     */
    public function end(?int $expiration = null): string
    {
        if (!$this->bufferStarted || $this->activeFragment === null) {
            return '';
        }

        $content    = ob_get_flush();
        $expiration = $expiration ?? self::DEFAULT_EXPIRATION;

        // Cache the content
        $this->cache->set($this->activeFragment, $content, $expiration, self::GROUP);

        // Reset state
        $this->activeFragment = null;
        $this->bufferStarted  = false;

        return $content;
    }

    /**
     * Render a fragment using a callback.
     *
     * @param string $key Fragment key.
     * @param callable $callback Callback that outputs the fragment.
     * @param int|null $expiration Cache expiration in seconds.
     * @param array $vary Variables that affect the fragment content.
     * @return void
     */
    public function render(string $key, callable $callback, ?int $expiration = null, array $vary = []): void
    {
        if ($this->start($key, $expiration, $vary)) {
            $callback();
            $this->end($expiration);
        }
    }

    /**
     * Get a cached fragment without outputting.
     *
     * @param string $key Fragment key.
     * @param array $vary Variables that affect the fragment content.
     * @return string|null
     */
    public function get(string $key, array $vary = []): ?string
    {
        $fullKey = $this->buildKey($key, $vary);
        return $this->cache->get($fullKey, null, self::GROUP);
    }

    /**
     * Set a fragment directly without capturing.
     *
     * @param string $key Fragment key.
     * @param string $content Fragment content.
     * @param int|null $expiration Cache expiration in seconds.
     * @param array $vary Variables that affect the fragment content.
     * @return bool
     */
    public function set(string $key, string $content, ?int $expiration = null, array $vary = []): bool
    {
        $fullKey    = $this->buildKey($key, $vary);
        $expiration = $expiration ?? self::DEFAULT_EXPIRATION;

        return $this->cache->set($fullKey, $content, $expiration, self::GROUP);
    }

    /**
     * Delete a cached fragment.
     *
     * @param string $key Fragment key.
     * @param array $vary Variables that affect the fragment content.
     * @return bool
     */
    public function delete(string $key, array $vary = []): bool
    {
        $fullKey = $this->buildKey($key, $vary);
        return $this->cache->delete($fullKey, self::GROUP);
    }

    /**
     * Check if a fragment exists in cache.
     *
     * @param string $key Fragment key.
     * @param array $vary Variables that affect the fragment content.
     * @return bool
     */
    public function has(string $key, array $vary = []): bool
    {
        $fullKey = $this->buildKey($key, $vary);
        return $this->cache->has($fullKey, self::GROUP);
    }

    /**
     * Build the full cache key including vary parameters.
     *
     * @param string $key Base key.
     * @param array $vary Variables that affect the content.
     * @return string
     */
    private function buildKey(string $key, array $vary = []): string
    {
        if (empty($vary)) {
            return $key;
        }

        // Sort for consistency
        ksort($vary);

        return $key . '_' . md5(serialize($vary));
    }

    /**
     * Create a key that varies by user.
     *
     * @param string $key Base key.
     * @return array Vary array with user ID.
     */
    public function varyByUser(string $key): array
    {
        return ['user_id' => get_current_user_id()];
    }

    /**
     * Create a key that varies by user role.
     *
     * @return array Vary array with user roles.
     */
    public function varyByRole(): array
    {
        $user  = wp_get_current_user();
        $roles = $user->roles ?? ['guest'];
        sort($roles);

        return ['roles' => implode(',', $roles)];
    }

    /**
     * Create a key that varies by locale.
     *
     * @return array Vary array with locale.
     */
    public function varyByLocale(): array
    {
        return ['locale' => get_locale()];
    }

    /**
     * Create a key that varies by URL.
     *
     * @return array Vary array with URL.
     */
    public function varyByUrl(): array
    {
        $requestUri = '';

        if (isset($_SERVER['REQUEST_URI'])) {
            $requestUri = esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']));
        }

        return ['url' => $requestUri];
    }

    /**
     * Create a key that varies by device type.
     *
     * @return array Vary array with device type.
     */
    public function varyByDevice(): array
    {
        $device = 'desktop';

        if (function_exists('wp_is_mobile') && wp_is_mobile()) {
            $device = 'mobile';
        }

        return ['device' => $device];
    }

    /**
     * Combine multiple vary conditions.
     *
     * @param array ...$conditions Vary conditions to combine.
     * @return array Combined vary array.
     */
    public function combineVary(array ...$conditions): array
    {
        return array_merge(...$conditions);
    }
}
