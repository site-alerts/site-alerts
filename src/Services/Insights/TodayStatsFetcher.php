<?php

namespace SiteAlerts\Services\Insights;

use SiteAlerts\Abstracts\AbstractSingleton;
use SiteAlerts\Database\DatabaseManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TodayStatsFetcher
 *
 * Fetches statistics for a specific day from the daily stats table.
 * Provides formatted data for alert generation and analysis.
 *
 * @package SiteAlerts\Services\Insights
 * @version 1.0.0
 */
class TodayStatsFetcher extends AbstractSingleton
{
    /**
     * Table name for daily stats.
     *
     * @var string
     */
    private string $statsTable;

    /**
     * Initialize the fetcher.
     */
    protected function __construct()
    {
        parent::__construct();
        $this->statsTable = DatabaseManager::getFullTableName('daily_stats');
    }

    /**
     * Register hooks and filters.
     *
     * @return void
     */
    public function register(): void
    {
        // Reserved for future hooks if needed
    }

    /**
     * Get statistics for a specific day.
     *
     * @param string $today Date in Y-m-d format.
     * @return array{pageviews: int, errors_404: int, top_404: ?array}
     */
    public function getToday(string $today): array
    {
        global $wpdb;

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name from DatabaseManager::getFullTableName()
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT pageviews, errors_404, top_404_json
                 FROM {$this->statsTable}
                 WHERE stats_date = %s
                 LIMIT 1",
                $today
            ),
            ARRAY_A
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter

        if (!is_array($row)) {
            return [
                'pageviews'  => 0,
                'errors_404' => 0,
                'top_404'    => null,
            ];
        }

        $top404 = null;
        if (!empty($row['top_404_json']) && is_string($row['top_404_json'])) {
            $decoded = json_decode($row['top_404_json'], true);
            if (is_array($decoded)) {
                $top404 = $decoded;
            }
        }

        return [
            'pageviews'  => (int)($row['pageviews'] ?? 0),
            'errors_404' => (int)($row['errors_404'] ?? 0),
            'top_404'    => $top404,
        ];
    }
}
