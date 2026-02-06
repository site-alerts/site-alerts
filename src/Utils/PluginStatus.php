<?php

namespace SiteAlerts\Utils;

use SiteAlerts\Models\DailyStats;
use SiteAlerts\Utils\DateTimeUtils;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class PluginStatus
 *
 * Utility class for determining the current plugin/monitoring status.
 * Provides reusable status checks that can be used across the plugin.
 *
 * @package SiteAlerts\Utils
 * @version 1.0.0
 */
class PluginStatus
{
    /**
     * Status constants
     */
    public const STATUS_FRESH   = 'fresh';
    public const STATUS_LIMITED = 'limited';
    public const STATUS_NORMAL  = 'normal';
    public const STATUS_ISSUE   = 'issue';

    /**
     * Option key for last daily run timestamp.
     */
    public const OPTION_LAST_DAILY_RUN = 'site_alerts_last_daily_run';

    /**
     * Baseline period in days.
     */
    public const BASELINE_DAYS = 7;

    /**
     * Hours threshold for issue status.
     */
    public const ISSUE_THRESHOLD_HOURS = 24;

    /**
     * Get the current monitoring status.
     *
     * Priority order:
     * 1. Fresh install (no cron has run)
     * 2. Issue (last run > 24 hours ago)
     * 3. Limited (< 7 days of data)
     * 4. Normal
     *
     * @return string One of the STATUS_* constants.
     */
    public static function getStatus(): string
    {
        // Check fresh install first
        if (self::isFreshInstall()) {
            return self::STATUS_FRESH;
        }

        // Check for issue (stale monitoring)
        $lastRun = self::getLastRunTimestamp();
        if ($lastRun !== null) {
            $hoursSinceLastRun = (DateTimeUtils::timestamp() - $lastRun) / 3600;
            if ($hoursSinceLastRun > self::ISSUE_THRESHOLD_HOURS) {
                return self::STATUS_ISSUE;
            }
        }

        // Check for limited data
        if (!self::isBaselineComplete()) {
            return self::STATUS_LIMITED;
        }

        return self::STATUS_NORMAL;
    }

    /**
     * Check if this is a fresh install (no cron has run yet).
     *
     * @return bool
     */
    public static function isFreshInstall(): bool
    {
        return self::getLastRunTimestamp() === null;
    }

    /**
     * Check if baseline period is complete (7+ days of data).
     *
     * @return bool
     */
    public static function isBaselineComplete(): bool
    {
        return self::getDaysWithData() >= self::BASELINE_DAYS;
    }

    /**
     * Get the timestamp of the last cron run.
     *
     * @return int|null Unix timestamp or null if never run.
     */
    public static function getLastRunTimestamp(): ?int
    {
        $timestamp = get_option(self::OPTION_LAST_DAILY_RUN, null);

        if ($timestamp === null || $timestamp === false || $timestamp === '') {
            return null;
        }

        return (int)$timestamp;
    }

    /**
     * Get number of days with collected data.
     *
     * @return int
     */
    public static function getDaysWithData(): int
    {
        global $wpdb;

        $table = DailyStats::getTableName();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

        return (int)$count;
    }

    /**
     * Check if data collection has started (at least one cron has run).
     *
     * @return bool
     */
    public static function hasStartedCollecting(): bool
    {
        return !self::isFreshInstall();
    }
}
