<?php

namespace SiteAlerts\Services\Frontend\Traffic;

use SiteAlerts\Abstracts\AbstractSingleton;
use SiteAlerts\Cache\CacheManager;
use SiteAlerts\Utils\CacheKeys;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class NotFoundTracker
 *
 * Tracks 404 errors on frontend requests.
 * Stores total count and a pruned map of paths that triggered 404s.
 *
 * @package SiteAlerts\Services\Frontend\Traffic
 * @version 1.0.0
 */
class NotFoundTracker extends AbstractSingleton
{
    /**
     * Transient TTL in seconds (10 days).
     */
    private const TRANSIENT_TTL = DAY_IN_SECONDS * 10;

    /**
     * Maximum number of paths to keep in the map.
     */
    private const MAX_PATHS = 30;

    /**
     * Maximum path length to store.
     */
    private const MAX_PATH_LENGTH = 180;

    /**
     * Register WordPress hooks.
     *
     * @return void
     */
    public function register(): void
    {
        add_action('template_redirect', [$this, 'maybeTrack404'], 1);
    }

    /**
     * Track 404 if this is a valid 404 request.
     *
     * @return void
     */
    public function maybeTrack404(): void
    {
        if ($this->shouldSkip()) {
            return;
        }

        if (!is_404()) {
            return;
        }

        $cache = CacheManager::getInstance();

        // Increase total 404 count
        $totalKey = CacheKeys::notFoundTotalToday();
        $cache->increment($totalKey, 1, self::TRANSIENT_TTL);

        // Track top paths
        $path = $this->getRequestPath();
        if ($path === '') {
            return;
        }

        $mapKey = CacheKeys::notFoundMapToday();
        $map    = $this->getMap($mapKey);

        $map[$path] = isset($map[$path]) ? ((int)$map[$path] + 1) : 1;

        $map = $this->pruneMap($map);

        $cache->set($mapKey, wp_json_encode($map), self::TRANSIENT_TTL);
    }

    /**
     * Determine if this request should be skipped.
     *
     * @return bool True if request should not be tracked.
     */
    private function shouldSkip(): bool
    {
        // Admin area
        if (is_admin()) {
            return true;
        }

        // REST requests
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return true;
        }

        // AJAX requests
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return true;
        }

        // Cron runs
        if (defined('DOING_CRON') && DOING_CRON) {
            return true;
        }

        return false;
    }

    /**
     * Get the request path without query parameters.
     *
     * @return string The sanitized path, or empty string if invalid.
     */
    private function getRequestPath(): string
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? (string)esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        if ($uri === '') {
            return '';
        }

        $path = wp_parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return '';
        }

        // Normalize
        $path = rawurldecode($path);
        $path = sanitize_text_field($path);

        // Remove trailing slash except root
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        // Limit length to avoid abuse
        if (strlen($path) > self::MAX_PATH_LENGTH) {
            $path = substr($path, 0, self::MAX_PATH_LENGTH);
        }

        return $path;
    }

    /**
     * Get the path map from cache.
     *
     * @param string $mapKey The cache key.
     * @return array The path → count map.
     */
    private function getMap(string $mapKey): array
    {
        $raw = CacheManager::getInstance()->get($mapKey);
        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Prune the map to keep only top paths by count.
     *
     * @param array $map The path → count map.
     * @return array The pruned map.
     */
    private function pruneMap(array $map): array
    {
        if (count($map) <= self::MAX_PATHS) {
            return $map;
        }

        arsort($map); // highest first
        return array_slice($map, 0, self::MAX_PATHS, true);
    }
}
