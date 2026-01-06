<?php

namespace SiteAlerts\Utils;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CacheKeys
 *
 * Centralized cache key definitions for the plugin.
 * All keys are used with CacheManager which adds the group prefix.
 *
 * @package SiteAlerts\Utils
 * @version 1.0.0
 */
class CacheKeys
{
    /**
     * Get the cache key for today's pageview count.
     *
     * @return string
     */
    public static function pageviewsToday(): string
    {
        return 'pv_' . DateTimeUtils::todayKey();
    }

    /**
     * Get the cache key for today's total 404 count.
     *
     * @return string
     */
    public static function notFoundTotalToday(): string
    {
        return '404_total_' . DateTimeUtils::todayKey();
    }

    /**
     * Get the cache key for today's 404 path map.
     *
     * @return string
     */
    public static function notFoundMapToday(): string
    {
        return '404_map_' . DateTimeUtils::todayKey();
    }

    /**
     * Get the cache key for the daily cron lock.
     *
     * @return string
     */
    public static function dailyLock(): string
    {
        return 'daily_lock';
    }

    /**
     * Get the cache key for admin notices.
     *
     * @return string
     */
    public static function adminNotices(): string
    {
        return 'admin_notices';
    }

    /**
     * Get the cache key for flush rewrite rules flag.
     *
     * @return string
     */
    public static function flushRewriteRules(): string
    {
        return 'flush_rewrite_rules';
    }
}
