<?php

namespace SiteAlerts\Services\Cron;

use SiteAlerts\Abstracts\AbstractSingleton;
use SiteAlerts\Cache\CacheManager;
use SiteAlerts\Models\DailyStats;
use SiteAlerts\Services\Insights\AlertEngine;
use SiteAlerts\Utils\DateTimeUtils;
use SiteAlerts\Utils\CacheKeys;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class DailyStatsFlusher
 *
 * Flushes transient counters (pageviews, 404s) to the database.
 * Runs daily via DailyCronHandler.
 *
 * @package SiteAlerts\Services\Cron
 * @version 1.0.0
 */
class DailyStatsFlusher extends AbstractSingleton
{
    /**
     * Execute the daily stats flush.
     *
     * @return void
     */
    public function run(): void
    {
        $cache = CacheManager::getInstance();

        // Simple lock to avoid double runs
        $lockKey = CacheKeys::dailyLock();
        if ($cache->get($lockKey)) {
            return;
        }
        $cache->set($lockKey, 1, MINUTE_IN_SECONDS * 5);

        try {
            // Flush YESTERDAY's data (the completed day when cron runs at midnight)
            $yesterday    = current_datetime()->modify('-1 day');
            $yesterdayYmd = $yesterday->format('Y-m-d');
            $yesterdayKey = $yesterday->format('Ymd');

            DailyStats::ensureDayExists($yesterdayYmd);

            $pageviews = (int)$cache->get(CacheKeys::pageviewsForDate($yesterdayKey), 0);
            $errors404 = (int)$cache->get(CacheKeys::notFoundTotalForDate($yesterdayKey), 0);
            $topMapRaw = $cache->get(CacheKeys::notFoundMapForDate($yesterdayKey));

            $topJson = $this->buildTop404Json($topMapRaw);

            DailyStats::updateDay($yesterdayYmd, $pageviews, $errors404, $topJson);
            AlertEngine::getInstance()->generateForDay($yesterdayYmd);

            // Delete yesterday's cache keys
            $cache->delete(CacheKeys::pageviewsForDate($yesterdayKey));
            $cache->delete(CacheKeys::notFoundTotalForDate($yesterdayKey));
            $cache->delete(CacheKeys::notFoundMapForDate($yesterdayKey));

            // Retention: keep 7 days
            $sevenDaysAgo = current_datetime()->modify('-7 days');
            $purgeBefore  = $sevenDaysAgo->format('Y-m-d');
            DailyStats::purgeOlderThan($purgeBefore);
            AlertEngine::getInstance()->purgeAlertsOlderThan($purgeBefore);

            update_option('site_alerts_last_daily_run', DateTimeUtils::timestamp(), false);
        } finally {
            $cache->delete($lockKey);
        }
    }

    /**
     * Convert stored map transient (JSON string) into a normalized top-3 JSON.
     *
     * @param mixed $topMapRaw The raw transient value.
     * @return string|null JSON string or null if invalid.
     */
    private function buildTop404Json($topMapRaw): ?string
    {
        if (!is_string($topMapRaw) || $topMapRaw === '') {
            return null;
        }

        $map = json_decode($topMapRaw, true);
        if (!is_array($map) || empty($map)) {
            return null;
        }

        $clean = [];
        foreach ($map as $path => $count) {
            $path = is_string($path) ? sanitize_text_field($path) : '';
            if ($path === '') {
                continue;
            }
            $clean[$path] = max(1, (int)$count);
        }

        if (empty($clean)) {
            return null;
        }

        arsort($clean);
        $top3 = array_slice($clean, 0, 3, true);

        // Store as list of [path, count] to keep order explicit
        $list = [];
        foreach ($top3 as $path => $count) {
            $list[] = [$path, $count];
        }

        return wp_json_encode($list);
    }
}
