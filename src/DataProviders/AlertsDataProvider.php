<?php

namespace SiteAlerts\DataProviders;

use SiteAlerts\Abstracts\AbstractDataProvider;
use SiteAlerts\Models\Alert;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AlertsDataProvider
 *
 * @package SiteAlerts\DataProviders
 * @version 1.0.0
 */
class AlertsDataProvider extends AbstractDataProvider
{
    /**
     * Get the latest alerts with enriched data.
     *
     * @param int $limit Number of alerts to retrieve (1-20).
     * @return array<int, array<string, mixed>>
     */
    public function getLatest(int $limit = 3): array
    {
        global $wpdb;

        $limit = max(1, min(20, $limit));
        $table = Alert::getTableName();

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name from trusted internal method
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT alert_date, type, severity, title, message, meta_json, created_at
                 FROM {$table}
                 ORDER BY alert_date DESC, id DESC
                 LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

        if (!is_array($rows)) {
            return [];
        }

        return array_map([$this, 'enrichAlert'], $rows);
    }

    /**
     * Get digest statistics for the last N days.
     *
     * @param int $days Number of days to look back.
     * @return array{traffic_alerts: int, error_alerts: int, critical_alerts: int, total_alerts: int}
     */
    public function getDigest(int $days = 7): array
    {
        global $wpdb;

        $table = Alert::getTableName();
        $start = wp_date('Y-m-d', strtotime(sprintf('-%d days', $days)));

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name from trusted internal method
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT type, severity FROM {$table} WHERE alert_date >= %s",
                $start
            ),
            ARRAY_A
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

        $traffic  = 0;
        $error    = 0;
        $critical = 0;

        if (is_array($rows)) {
            foreach ($rows as $row) {
                $type     = isset($row['type']) ? (string)$row['type'] : '';
                $severity = isset($row['severity']) ? strtolower((string)$row['severity']) : '';

                if ($type === 'traffic_drop' || $type === 'traffic_spike') {
                    $traffic++;
                } elseif ($type === 'error_404_spike') {
                    $error++;
                }

                if ($severity === 'critical') {
                    $critical++;
                }
            }
        }

        return [
            'traffic_alerts'  => $traffic,
            'error_alerts'    => $error,
            'critical_alerts' => $critical,
            'total_alerts'    => is_array($rows) ? count($rows) : 0,
        ];
    }

    /**
     * Enrich alert data with computed fields.
     *
     * @param array<string, mixed> $alert Raw alert data.
     * @return array<string, mixed> Enriched alert data.
     */
    private function enrichAlert(array $alert): array
    {
        $type     = isset($alert['type']) ? (string)$alert['type'] : '';
        $severity = isset($alert['severity']) ? strtolower((string)$alert['severity']) : 'info';

        $alert['icon_class']     = $this->getIconClass($type);
        $alert['severity_class'] = $this->getSeverityClass($severity);
        $alert['type_label']     = $this->getTypeLabel($type);

        return $alert;
    }

    /**
     * Get CSS class for alert type icon.
     *
     * @param string $type Alert type.
     * @return string CSS class.
     */
    private function getIconClass(string $type): string
    {
        switch ($type) {
            case 'traffic_drop':
                return 'sa-icon--traffic-drop';
            case 'traffic_spike':
                return 'sa-icon--traffic-spike';
            case 'error_404_spike':
                return 'sa-icon--error-404';
            default:
                return 'sa-icon--alert';
        }
    }

    /**
     * Get CSS class for severity badge.
     *
     * @param string $severity Alert severity.
     * @return string CSS class.
     */
    private function getSeverityClass(string $severity): string
    {
        switch ($severity) {
            case 'warning':
                return 'sa-badge--warning';
            case 'critical':
                return 'sa-badge--danger';
            default:
                return 'sa-badge--info';
        }
    }

    /**
     * Get human-readable label for alert type.
     *
     * @param string $type Alert type.
     * @return string Translated label.
     */
    private function getTypeLabel(string $type): string
    {
        switch ($type) {
            case 'traffic_drop':
                return __('Traffic Drop', 'site-alerts');
            case 'traffic_spike':
                return __('Traffic Spike', 'site-alerts');
            case 'error_404_spike':
                return __('404 Spike', 'site-alerts');
            default:
                return __('Alert', 'site-alerts');
        }
    }

    /**
     * Get aggregated top 404 URLs from recent alerts.
     *
     * Extracts and aggregates the 'top' array from error_404_spike alert meta_json.
     *
     * @param int $days Number of days to look back.
     * @param int $limit Number of top URLs to return.
     * @return array<int, array{url: string, count: int}>
     */
    public function getTop404Urls(int $days = 7, int $limit = 3): array
    {
        global $wpdb;

        $table = Alert::getTableName();
        $start = wp_date('Y-m-d', strtotime(sprintf('-%d days', $days)));

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name from trusted internal method
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT meta_json FROM {$table}
                 WHERE type = 'error_404_spike'
                 AND alert_date >= %s
                 AND meta_json IS NOT NULL",
                $start
            ),
            ARRAY_A
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

        if (!is_array($rows)) {
            return [];
        }

        // Aggregate counts across alerts
        $aggregated = [];
        foreach ($rows as $row) {
            $meta = json_decode($row['meta_json'], true);
            if (is_array($meta) && isset($meta['top']) && is_array($meta['top'])) {
                foreach ($meta['top'] as $item) {
                    if (is_array($item) && isset($item[0], $item[1])) {
                        $path              = (string)$item[0];
                        $count             = (int)$item[1];
                        $aggregated[$path] = ($aggregated[$path] ?? 0) + $count;
                    }
                }
            }
        }

        arsort($aggregated);
        $top = array_slice($aggregated, 0, $limit, true);

        $result = [];
        foreach ($top as $url => $count) {
            $result[] = ['url' => $url, 'count' => $count];
        }

        return $result;
    }
}
