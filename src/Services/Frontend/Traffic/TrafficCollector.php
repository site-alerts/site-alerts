<?php

namespace SiteAlerts\Services\Frontend\Traffic;

use SiteAlerts\Abstracts\AbstractSingleton;
use SiteAlerts\Cache\CacheManager;
use SiteAlerts\Utils\CacheKeys;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TrafficCollector
 *
 * Collects frontend pageview counts using WordPress transients.
 * Runs only on legitimate frontend requests, skipping admin, REST, AJAX,
 * cron, feed, and preview requests.
 *
 * @package SiteAlerts\Services\Frontend\Traffic
 * @version 1.0.0
 */
class TrafficCollector extends AbstractSingleton
{
    /**
     * Transient TTL in seconds (10 days).
     * Long TTL ensures transient survives until daily cron processes it.
     */
    private const TRANSIENT_TTL = DAY_IN_SECONDS * 10;

    /**
     * Register WordPress hooks.
     *
     * @return void
     */
    public function register(): void
    {
        // Run late enough so WP query is ready, but before output
        add_action('wp', [$this, 'maybeCountPageview'], 20);
    }

    /**
     * Increment pageview count if this is a valid frontend request.
     *
     * @return void
     */
    public function maybeCountPageview(): void
    {
        if ($this->shouldSkip()) {
            return;
        }

        $key = CacheKeys::pageviewsToday();
        CacheManager::getInstance()->increment($key, 1, self::TRANSIENT_TTL);
    }

    /**
     * Determine if this request should be skipped.
     *
     * @return bool True if request should not be counted.
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

        // Feed requests
        if (is_feed()) {
            return true;
        }

        // Preview requests
        if (is_preview()) {
            return true;
        }

        return false;
    }
}
