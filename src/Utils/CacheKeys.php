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
     * Get the cache key for pageview count for a specific date.
     *
     * @param string $dateKey Date key in Ymd format (e.g., '20250107').
     * @return string
     */
    public static function pageviewsForDate(string $dateKey): string
    {
        return 'pv_' . $dateKey;
    }

    /**
     * Get the cache key for 404 count for a specific date.
     *
     * @param string $dateKey Date key in Ymd format (e.g., '20250107').
     * @return string
     */
    public static function notFoundTotalForDate(string $dateKey): string
    {
        return '404_total_' . $dateKey;
    }

    /**
     * Get the cache key for 404 path map for a specific date.
     *
     * @param string $dateKey Date key in Ymd format (e.g., '20250107').
     * @return string
     */
    public static function notFoundMapForDate(string $dateKey): string
    {
        return '404_map_' . $dateKey;
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
