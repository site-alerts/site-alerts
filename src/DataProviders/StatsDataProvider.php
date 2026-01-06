<?php

namespace SiteAlerts\DataProviders;

use SiteAlerts\Abstracts\AbstractDataProvider;
use SiteAlerts\Models\DailyStats;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class StatsDataProvider
 *
 * @package SiteAlerts\DataProviders
 * @version 1.0.0
 */
class StatsDataProvider extends AbstractDataProvider
{
    /**
     * Get stats for the last N days.
     *
     * @param int $days Number of days to retrieve (1-90).
     * @return array<int, array<string, mixed>>
     */
    public function getLastDays(int $days = 7): array
    {
        global $wpdb;

        $days  = max(1, min(90, $days));
        $table = DailyStats::getTableName();

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name from trusted internal method
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT stats_date, pageviews, errors_404
                 FROM {$table}
                 ORDER BY stats_date DESC
                 LIMIT %d",
                $days
            ),
            ARRAY_A
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

        return is_array($rows) ? $rows : [];
    }

    /**
     * Get weekly digest with alert counts and worst day stats.
     *
     * @return array{traffic_alerts: int, error_alerts: int, worst_day: ?string, worst_drop_pct: ?float}
     */
    public function getWeeklyDigest(): array
    {
        $days = $this->getLastDays(7);

        if (empty($days)) {
            return [
                'traffic_alerts' => 0,
                'error_alerts'   => 0,
                'worst_day'      => null,
                'worst_drop_pct' => null,
            ];
        }

        // Sort ascending by date for comparison
        usort($days, static function ($a, $b) {
            return strcmp((string)$a['stats_date'], (string)$b['stats_date']);
        });

        $trafficAlerts = 0;
        $errorAlerts   = 0;
        $worstDay      = null;
        $worstDropPct  = null;
        $prevPv        = null;

        foreach ($days as $d) {
            $pv   = (int)($d['pageviews'] ?? 0);
            $e404 = (int)($d['errors_404'] ?? 0);

            if ($prevPv !== null && $prevPv > 0) {
                $dropPct = (($pv / $prevPv) - 1.0) * 100.0;
                if ($dropPct <= -30.0) {
                    $trafficAlerts++;
                }
                if ($worstDropPct === null || $dropPct < $worstDropPct) {
                    $worstDropPct = $dropPct;
                    $worstDay     = (string)$d['stats_date'];
                }
            }

            if ($e404 >= 10) {
                $errorAlerts++;
            }

            $prevPv = $pv;
        }

        return [
            'traffic_alerts' => $trafficAlerts,
            'error_alerts'   => $errorAlerts,
            'worst_day'      => $worstDay,
            'worst_drop_pct' => $worstDropPct,
        ];
    }
}
