<?php

namespace SiteAlerts\Services\Insights;

use SiteAlerts\Abstracts\AbstractSingleton;
use SiteAlerts\Database\DatabaseManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class BaselineCalculator
 *
 * Calculates baseline statistics from historical daily data.
 * Used for comparing current day metrics against recent averages.
 *
 * @package SiteAlerts\Services\Insights
 * @version 1.0.0
 */
class BaselineCalculator extends AbstractSingleton
{
    /**
     * Table name for daily stats.
     *
     * @var string
     */
    private string $statsTable;

    /**
     * Initialize the calculator.
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
     * Get average stats for the last N days, excluding today.
     *
     * @param string $today Date in Y-m-d format.
     * @param int $days Number of days to average.
     * @return array{count: int, avg_pageviews: float, avg_404: float}
     */
    public function getAvgLastNDaysExcludingToday(string $today, int $days = 7): array
    {
        global $wpdb;

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name from DatabaseManager::getFullTableName()
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT pageviews, errors_404
                 FROM {$this->statsTable}
                 WHERE stats_date < %s
                 ORDER BY stats_date DESC
                 LIMIT %d",
                $today,
                $days
            ),
            ARRAY_A
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter

        if (!is_array($rows) || empty($rows)) {
            return [
                'count'         => 0,
                'avg_pageviews' => 0.0,
                'avg_404'       => 0.0,
            ];
        }

        $count  = count($rows);
        $sumPv  = 0;
        $sum404 = 0;

        foreach ($rows as $row) {
            $sumPv  += (int)($row['pageviews'] ?? 0);
            $sum404 += (int)($row['errors_404'] ?? 0);
        }

        return [
            'count'         => $count,
            'avg_pageviews' => $count > 0 ? ($sumPv / $count) : 0.0,
            'avg_404'       => $count > 0 ? ($sum404 / $count) : 0.0,
        ];
    }
}
