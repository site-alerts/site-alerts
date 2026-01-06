<?php

namespace SiteAlerts\Services\Insights;

use SiteAlerts\Abstracts\AbstractSingleton;
use SiteAlerts\Models\Alert;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AlertEngine
 *
 * Generates alerts based on traffic patterns and error rates.
 * Compares current day statistics against historical baselines
 * to detect anomalies like traffic drops, spikes, and 404 surges.
 *
 * @package SiteAlerts\Services\Insights
 * @version 1.0.0
 */
class AlertEngine extends AbstractSingleton
{
    /**
     * Baseline calculator service.
     *
     * @var BaselineCalculator
     */
    private BaselineCalculator $baseline;

    /**
     * Today stats fetcher service.
     *
     * @var TodayStatsFetcher
     */
    private TodayStatsFetcher $todayFetcher;

    /**
     * Initialize the engine with its dependencies.
     */
    protected function __construct()
    {
        parent::__construct();
        $this->baseline     = BaselineCalculator::getInstance();
        $this->todayFetcher = TodayStatsFetcher::getInstance();
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
     * Generate alerts for a specific day.
     *
     * @param string $today Date in Y-m-d format.
     * @return void
     */
    public function generateForDay(string $today): void
    {
        $base = $this->baseline->getAvgLastNDaysExcludingToday($today, 7);

        if (($base['count'] ?? 0) < 7) {
            return;
        }

        $avgPv  = (float)$base['avg_pageviews'];
        $avg404 = (float)$base['avg_404'];

        $todayStats = $this->todayFetcher->getToday($today);
        $todayPv    = (int)$todayStats['pageviews'];
        $today404   = (int)$todayStats['errors_404'];
        $top404     = $todayStats['top_404'];

        $this->trafficRules($today, $todayPv, $avgPv);
        $this->error404Rules($today, $today404, $avg404, $top404);
    }

    /**
     * Apply traffic-based alert rules.
     *
     * @param string $date Date in Y-m-d format.
     * @param int $todayPv Today's pageview count.
     * @param float $avgPv Average pageviews over baseline period.
     * @return void
     */
    private function trafficRules(string $date, int $todayPv, float $avgPv): void
    {
        if ($avgPv <= 0.0) {
            return;
        }

        $ratio     = $todayPv / $avgPv;
        $changePct = ($ratio - 1.0) * 100.0;

        // Traffic drop detection
        if ($ratio < 0.7) {
            $severity = (abs($changePct) >= 40.0) ? 'critical' : 'warning';
            $title    = sprintf(
            /* translators: %s is percent number */
                __('Traffic dropped by %s%%', 'site-alerts'),
                number_format_i18n(abs($changePct), 0)
            );

            $message = __(
                "Today's traffic is significantly lower than your recent average.\n\nNext steps:\n- Check if your site is reachable\n- Review recent plugin/theme changes\n- Check for increased 404 errors",
                'site-alerts'
            );

            $meta = [
                'today'      => $todayPv,
                'avg7'       => (int)round($avgPv),
                'change_pct' => round($changePct, 2),
            ];

            Alert::createIfNotExists(
                $date,
                'traffic_drop',
                $severity,
                $title,
                $message,
                wp_json_encode($meta)
            );
        }

        // Traffic spike detection
        if ($ratio > 1.5) {
            $title = sprintf(
            /* translators: %s is percent number */
                __('Traffic increased by %s%%', 'site-alerts'),
                number_format_i18n(abs($changePct), 0)
            );

            $message = __(
                "Your traffic is significantly higher than your recent average.\n\nNext steps:\n- Check which pages are receiving traffic\n- Verify traffic sources\n- Ensure your site performance is stable",
                'site-alerts'
            );

            $meta = [
                'today'      => $todayPv,
                'avg7'       => (int)round($avgPv),
                'change_pct' => round($changePct, 2),
            ];

            Alert::createIfNotExists(
                $date,
                'traffic_spike',
                'info',
                $title,
                $message,
                wp_json_encode($meta)
            );
        }
    }

    /**
     * Apply 404 error-based alert rules.
     *
     * @param string $date Date in Y-m-d format.
     * @param int $today404 Today's 404 error count.
     * @param float $avg404 Average 404 errors over baseline period.
     * @param array|null $top404 Top 404 paths array.
     * @return void
     */
    private function error404Rules(string $date, int $today404, float $avg404, ?array $top404): void
    {
        if ($today404 <= 0) {
            return;
        }

        $shouldAlert = false;

        if ($avg404 < 3.0) {
            if ($today404 >= 10) {
                $shouldAlert = true;
            }
        } else if ($today404 > 2.0 * $avg404) {
            $shouldAlert = true;
        }

        if (!$shouldAlert) {
            return;
        }

        $title = __('404 errors increased', 'site-alerts');

        $topPath = null;
        if (is_array($top404) && !empty($top404) && is_array($top404[0]) && isset($top404[0][0])) {
            $topPath = (string)$top404[0][0];
        }

        $line = $topPath
            /* translators: %s is the URL path */
            ? sprintf(__('Most affected URL: %s', 'site-alerts'), $topPath)
            : __('Most affected URL: (not available)', 'site-alerts');

        $message = sprintf(
            "%s\n\n%s",
            __(
                "Your site is receiving significantly more 404 errors than usual.\n\nNext steps:\n- Add a redirect for missing pages\n- Fix internal links pointing to missing URLs\n- Check recent permalink changes",
                'site-alerts'
            ),
            $line
        );

        $changePct = null;
        if ($avg404 > 0.0) {
            $changePct = round((($today404 / $avg404) - 1.0) * 100.0, 2);
        }

        $meta = [
            'today'      => $today404,
            'avg7'       => (int)round($avg404),
            'change_pct' => $changePct,
            'top'        => $top404,
        ];

        Alert::createIfNotExists(
            $date,
            'error_404_spike',
            'warning',
            $title,
            $message,
            wp_json_encode($meta)
        );
    }

    /**
     * Purge alerts older than the given date.
     *
     * @param string $dateYmd Date in Y-m-d format.
     * @return void
     */
    public function purgeAlertsOlderThan(string $dateYmd): void
    {
        Alert::purgeOlderThan($dateYmd);
    }
}
