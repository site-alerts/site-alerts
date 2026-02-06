<?php

namespace SiteAlerts\Services\Frontend\Traffic;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class PageviewSignal
 *
 * Determines whether the current request represents a real pageview signal.
 *
 * A valid pageview signal must:
 * - Reach the template rendering stage
 * - Belong to the main query (user intent)
 * - Not be an admin, system, or background request
 * - Not target a static file or asset
 *
 * This class is intentionally final and static:
 * it represents a fixed domain rule used for anomaly detection,
 * * not a customizable tracking behavior.
 *
 * @package SiteAlerts\Services\Frontend\Traffic
 * @version 1.0.0
 */
class PageviewSignal
{
    /**
     * Check if the current request should be collected as a pageview signal.
     *
     * @return bool True if the request represents a real user pageview.
     */
    public static function shouldCollect(): bool
    {
        /**
         * Ensure WordPress has fully initialized the main query
         * before evaluating the request as a pageview.
         */
        if (!did_action('wp')) {
            return false;
        }

        // Only the main query represents actual user intent
        if (!is_main_query()) {
            return false;
        }

        // Exclude admin, system, and background requests
        if (
            (defined('REST_REQUEST') && REST_REQUEST) ||
            (defined('DOING_AJAX') && DOING_AJAX) ||
            (defined('DOING_CRON') && DOING_CRON) ||
            is_admin() ||
            is_favicon() ||
            is_feed() ||
            is_preview()
        ) {
            return false;
        }

        /**
         * Ignore non-navigation requests (prefetch, prerender, speculative fetches).
         * Only real user page navigations should be counted.
         */
        if (
            isset($_SERVER['HTTP_SEC_FETCH_MODE']) &&
            !in_array($_SERVER['HTTP_SEC_FETCH_MODE'], ['navigate', 'nested-navigate'], true)
        ) {
            return false;
        }

        // Exclude static assets and file-like URLs
        $uri  = isset($_SERVER['REQUEST_URI']) ? (string)esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        $path = wp_parse_url($uri, PHP_URL_PATH);
        return !($path && pathinfo($path, PATHINFO_EXTENSION));
    }
}