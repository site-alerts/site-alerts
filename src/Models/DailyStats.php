<?php

namespace SiteAlerts\Models;

use SiteAlerts\Abstracts\AbstractModel;
use SiteAlerts\Database\DatabaseManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class DailyStats
 *
 * Model for daily statistics including pageviews and 404 errors.
 * Stores one record per day with aggregated traffic data.
 *
 * @package SiteAlerts\Models
 * @version 1.0.0
 */
class DailyStats extends AbstractModel
{
    /**
     * Table name (without prefix)
     *
     * @var string
     */
    protected static string $table = 'daily_stats';

    /**
     * Fillable fields (allowed for mass assignment)
     *
     * @var array
     */
    protected static array $fillable = [
        'stats_date',
        'pageviews',
        'errors_404',
        'top_404_json',
    ];

    /**
     * Attribute type casts
     *
     * @var array
     */
    protected static array $casts = [
        'pageviews'    => 'integer',
        'errors_404'   => 'integer',
        'top_404_json' => 'json',
    ];


    /**
     * Ensure a row exists for the given date.
     *
     * @param string $dateYmd Date in Y-m-d format.
     * @return void
     */
    public static function ensureDayExists(string $dateYmd): void
    {
        $table = static::getTableName();
        $now   = current_time('mysql');

        DatabaseManager::preparedQuery(
            "INSERT IGNORE INTO {$table} (stats_date, pageviews, errors_404, top_404_json, created_at, updated_at)
             VALUES (%s, 0, 0, NULL, %s, %s)",
            $dateYmd,
            $now,
            $now
        );
    }

    /**
     * Update stats for a given date.
     *
     * @param string $dateYmd Date in Y-m-d format.
     * @param int $pageviews Pageview count.
     * @param int $errors404 404 error count.
     * @param string|null $top404Json JSON string of top 404 paths.
     * @return void
     */
    public static function updateDay(string $dateYmd, int $pageviews, int $errors404, ?string $top404Json): void
    {
        $table = static::getTableName();
        $now   = current_time('mysql');

        $existingJson = static::getTop404JsonForDate($dateYmd);
        $mergedJson   = static::mergeTop404Json($existingJson, $top404Json);

        DatabaseManager::preparedQuery(
            "UPDATE {$table} SET
                pageviews = pageviews + %d,
                errors_404 = errors_404 + %d,
                top_404_json = %s,
                updated_at = %s
            WHERE stats_date = %s",
            max(0, $pageviews),
            max(0, $errors404),
            $mergedJson,
            $now,
            $dateYmd
        );
    }

    /**
     * Get the top_404_json for a specific date.
     *
     * @param string $dateYmd Date in Y-m-d format.
     * @return string|null JSON string or null if not found.
     */
    private static function getTop404JsonForDate(string $dateYmd): ?string
    {
        $row = DatabaseManager::getRow(static::$table, ['stats_date' => $dateYmd]);

        return $row->top_404_json ?? null;
    }

    /**
     * Merge two top 404 JSON arrays, combining counts for same paths.
     *
     * @param string|null $existingJson Existing JSON from database.
     * @param string|null $newJson New JSON to merge.
     * @return string|null Merged JSON string or null if both empty.
     */
    private static function mergeTop404Json(?string $existingJson, ?string $newJson): ?string
    {
        $existing = $existingJson ? json_decode($existingJson, true) : [];
        $new      = $newJson ? json_decode($newJson, true) : [];

        if (empty($new)) {
            return $existingJson;
        }

        if (empty($existing)) {
            return $newJson;
        }

        $merged = [];
        foreach ($existing as $item) {
            if (is_array($item) && isset($item[0], $item[1])) {
                $merged[$item[0]] = (int)$item[1];
            }
        }
        foreach ($new as $item) {
            if (is_array($item) && isset($item[0], $item[1])) {
                $path          = $item[0];
                $count         = (int)$item[1];
                $merged[$path] = ($merged[$path] ?? 0) + $count;
            }
        }

        arsort($merged);
        $top3 = array_slice($merged, 0, 3, true);

        $result = [];
        foreach ($top3 as $path => $count) {
            $result[] = [$path, $count];
        }

        return wp_json_encode($result);
    }

    /**
     * Delete records older than the given date.
     *
     * @param string $dateYmd Date in Y-m-d format.
     * @return void
     */
    public static function purgeOlderThan(string $dateYmd): void
    {
        $table = static::getTableName();

        DatabaseManager::preparedQuery(
            "DELETE FROM {$table} WHERE stats_date < %s",
            $dateYmd
        );
    }

    /**
     * Delete the record for a specific date.
     *
     * @param string $dateYmd Date in Y-m-d format.
     * @return void
     */
    public static function deleteByDate(string $dateYmd): void
    {
        $table = static::getTableName();

        DatabaseManager::preparedQuery(
            "DELETE FROM {$table} WHERE stats_date = %s",
            $dateYmd
        );
    }
}