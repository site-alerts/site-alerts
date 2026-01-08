<?php

namespace SiteAlerts\Services\Admin\Alerts;

use SiteAlerts\DataProviders\AlertsDataProvider;
use SiteAlerts\DataProviders\StatsDataProvider;
use SiteAlerts\Utils\DateTimeUtils;
use SiteAlerts\Utils\PluginStatus;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AlertsPageContext
 *
 * Builds all state-aware section data for the Alerts admin page.
 * Centralizes the logic for generating context based on monitoring status.
 *
 * @package SiteAlerts\Services\Admin\Alerts
 * @version 1.0.0
 */
class AlertsPageContext
{
    /**
     * Current monitoring status.
     *
     * @var string
     */
    private string $status;

    /**
     * Raw digest data from provider.
     *
     * @var array
     */
    private array $rawDigest;

    /**
     * Raw alerts data from provider.
     *
     * @var array
     */
    private array $rawAlerts;

    /**
     * Raw history data from provider.
     *
     * @var array
     */
    private array $rawHistory;

    /**
     * Alerts data provider.
     *
     * @var AlertsDataProvider
     */
    private AlertsDataProvider $alertsProvider;

    /**
     * Stats data provider.
     *
     * @var StatsDataProvider
     */
    private StatsDataProvider $statsProvider;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->alertsProvider = AlertsDataProvider::getInstance();
        $this->statsProvider  = StatsDataProvider::getInstance();

        $this->status     = PluginStatus::getStatus();
        $this->rawDigest  = $this->alertsProvider->getDigest(7);
        $this->rawAlerts  = $this->alertsProvider->getLatest(5);
        $this->rawHistory = $this->statsProvider->getLastDays(7);
    }

    /**
     * Get the current monitoring status.
     *
     * @return string One of PluginStatus::STATUS_* constants.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Check if there are any alerts.
     *
     * @return bool
     */
    public function hasAlerts(): bool
    {
        return !empty($this->rawAlerts);
    }

    /**
     * Get total alert count.
     *
     * @return int
     */
    public function getAlertCount(): int
    {
        return (int)($this->rawDigest['total_alerts'] ?? 0);
    }

    /**
     * Get status summary box data.
     *
     * @return array{color: string, title: string, text: string}
     */
    public function getStatusSummary(): array
    {
        $alertCount = $this->getAlertCount();

        switch ($this->status) {
            case PluginStatus::STATUS_FRESH:
                return [
                    'color' => 'info',
                    'title' => __('Getting started', 'site-alerts'),
                    'text'  => __("We're collecting baseline data. Your first insights will appear shortly.", 'site-alerts'),
                ];

            case PluginStatus::STATUS_LIMITED:
                return [
                    'color' => 'info',
                    'title' => __('Limited history', 'site-alerts'),
                    'text'  => __('Insights will improve as more data is collected. Check back soon for more accurate detection.', 'site-alerts'),
                ];

            case PluginStatus::STATUS_ISSUE:
                return [
                    'color' => 'warning',
                    'title' => __('Check monitoring', 'site-alerts'),
                    'text'  => __('Last check was over 24 hours ago. Verify your site cron is running.', 'site-alerts'),
                ];

            default:
                $criticalCount = (int)($this->rawDigest['critical_alerts'] ?? 0);

                if ($criticalCount > 0) {
                    return [
                        'color' => 'danger',
                        'title' => __('Critical issues detected', 'site-alerts'),
                        'text'  => sprintf(
                        /* translators: %d: number of critical alerts */
                            _n(
                                '%d critical issue detected in the last 7 days that requires immediate attention.',
                                '%d critical issues detected in the last 7 days that require immediate attention.',
                                $criticalCount,
                                'site-alerts'
                            ),
                            $criticalCount
                        ),
                    ];
                }

                if ($alertCount > 0) {
                    return [
                        'color' => 'warning',
                        'title' => __('Attention needed', 'site-alerts'),
                        'text'  => sprintf(
                        /* translators: %d: number of alerts */
                            _n(
                                '%d alert detected in the last 7 days that may need your attention.',
                                '%d alerts detected in the last 7 days that may need your attention.',
                                $alertCount,
                                'site-alerts'
                            ),
                            $alertCount
                        ),
                    ];
                }

                return [
                    'color' => 'success',
                    'title' => __('All clear', 'site-alerts'),
                    'text'  => __("No unusual activity detected in the last 7 days. We'll keep monitoring and surface issues with recommended actions.", 'site-alerts'),
                ];
        }
    }

    /**
     * Get digest cards data with state-aware values and subtitles.
     *
     * @return array Array of card configurations.
     */
    public function getDigestCards(): array
    {
        $cards = $this->getCardDefinitions();

        foreach ($cards as $key => &$card) {
            $rawValue  = $this->rawDigest[$key] ?? 0;
            $stateData = $this->getCardStateData($key, (int)$rawValue);

            $card['value']    = $stateData['value'];
            $card['subtitle'] = $stateData['subtitle'];
        }

        return $cards;
    }

    /**
     * Get base card definitions.
     *
     * @return array
     */
    private function getCardDefinitions(): array
    {
        return [
            'critical_alerts' => [
                'iconClass' => 'sa-icon--critical',
                'label'     => __('Critical Alerts', 'site-alerts'),
                'color'     => 'danger',
            ],
            'traffic_alerts'  => [
                'iconClass' => 'sa-icon--traffic',
                'label'     => __('Traffic Alerts', 'site-alerts'),
                'color'     => 'primary',
            ],
            'error_alerts'    => [
                'iconClass' => 'sa-icon--error-404',
                'label'     => __('404 Alerts', 'site-alerts'),
                'color'     => 'warning',
            ],
            'total_alerts'    => [
                'iconClass' => 'sa-icon--alert',
                'label'     => __('Total Alerts', 'site-alerts'),
                'color'     => 'info',
            ],
        ];
    }

    /**
     * Get state-aware value and subtitle for a card.
     *
     * @param string $cardKey Card identifier.
     * @param int $rawValue Raw count value.
     * @return array{value: string, subtitle: string}
     */
    private function getCardStateData(string $cardKey, int $rawValue): array
    {
        // Fresh install: show dash, collecting data
        if ($this->status === PluginStatus::STATUS_FRESH) {
            return [
                'value'    => '—',
                'subtitle' => $cardKey === 'total_alerts'
                    ? __('Last 7 days', 'site-alerts')
                    : __('Collecting data', 'site-alerts'),
            ];
        }

        // Limited data: show actual value with limited notice
        if ($this->status === PluginStatus::STATUS_LIMITED) {
            return [
                'value'    => (string)$rawValue,
                'subtitle' => __('Limited data available', 'site-alerts'),
            ];
        }

        // Normal/Issue states: show actual values with appropriate subtitles
        if ($rawValue === 0) {
            return [
                'value'    => '0',
                'subtitle' => $this->getZeroSubtitle($cardKey),
            ];
        }

        return [
            'value'    => (string)$rawValue,
            'subtitle' => $this->getActiveSubtitle($cardKey),
        ];
    }

    /**
     * Get subtitle for zero-value cards.
     *
     * @param string $cardKey Card identifier.
     * @return string
     */
    private function getZeroSubtitle(string $cardKey): string
    {
        switch ($cardKey) {
            case 'traffic_alerts':
                return __('No unusual traffic detected', 'site-alerts');
            case 'error_alerts':
                return __('No 404 issues detected', 'site-alerts');
            case 'critical_alerts':
                return __('No critical issues detected', 'site-alerts');
            default:
                return __('Last 7 days', 'site-alerts');
        }
    }

    /**
     * Get subtitle for cards with alerts.
     *
     * @param string $cardKey Card identifier.
     * @return string
     */
    private function getActiveSubtitle(string $cardKey): string
    {
        switch ($cardKey) {
            case 'traffic_alerts':
                return __('Unusual traffic changes detected', 'site-alerts');
            case 'error_alerts':
                return __('Pages returning 404 errors', 'site-alerts');
            case 'critical_alerts':
                return __('Issues needing attention', 'site-alerts');
            default:
                return __('Total in last 7 days', 'site-alerts');
        }
    }

    /**
     * Get latest alerts data.
     *
     * Returns either a message card config (for empty states) or alert cards array.
     *
     * @return array{type: string, ...}
     */
    public function getLatestAlerts(): array
    {
        // Show message card for fresh/limited/all-clear states
        if ($this->status === PluginStatus::STATUS_FRESH) {
            return [
                'type'   => 'message',
                'title'  => __('Getting started', 'site-alerts'),
                'text'   => __("We're collecting baseline data for your site. Your first insights will appear shortly.", 'site-alerts'),
                'helper' => __('Tip: Leave this plugin active to get accurate insights.', 'site-alerts'),
            ];
        }

        if ($this->status === PluginStatus::STATUS_LIMITED) {
            return [
                'type'   => 'message',
                'title'  => __('Limited history', 'site-alerts'),
                'text'   => __('Not enough data yet to reliably detect unusual activity.', 'site-alerts'),
                'helper' => __('Insights will improve as more data is collected.', 'site-alerts'),
            ];
        }

        // No alerts in normal/issue state
        if (empty($this->rawAlerts)) {
            return [
                'type'   => 'message',
                'title'  => __('All clear', 'site-alerts'),
                'text'   => __('No unusual activity detected in the last 7 days.', 'site-alerts'),
                'helper' => __("We'll keep monitoring and notify you if something changes.", 'site-alerts'),
                'icon'   => 'sa-icon--check-circle',
                'color'  => 'success',
            ];
        }

        // Return alert cards with expanded content
        return [
            'type'   => 'alerts',
            'alerts' => $this->enrichAlertsWithExpandedContent($this->rawAlerts),
        ];
    }

    /**
     * Enrich alerts with expanded "What this means" and "What to check" content.
     *
     * @param array $alerts Raw alerts.
     * @return array Enriched alerts.
     */
    private function enrichAlertsWithExpandedContent(array $alerts): array
    {
        // Sort by severity: critical > warning > info
        usort($alerts, static function ($a, $b) {
            $order  = ['critical' => 0, 'warning' => 1, 'info' => 2];
            $aOrder = $order[$a['severity'] ?? 'info'] ?? 2;
            $bOrder = $order[$b['severity'] ?? 'info'] ?? 2;

            return $aOrder - $bOrder;
        });

        foreach ($alerts as &$alert) {
            $type = $alert['type'] ?? '';

            $alert['short_message'] = $this->getShortMessage($type);
            $alert['expanded']      = $this->getExpandedContent($type, $alert);
        }

        return $alerts;
    }

    /**
     * Get short collapsed message for alert type.
     *
     * @param string $type Alert type.
     * @return string
     */
    private function getShortMessage(string $type): string
    {
        switch ($type) {
            case 'traffic_drop':
                return __('Traffic dropped unexpectedly compared to recent days.', 'site-alerts');
            case 'traffic_spike':
                return __('Traffic increased significantly compared to recent days.', 'site-alerts');
            case 'error_404_spike':
                return __('Visitors are reaching pages that no longer exist.', 'site-alerts');
            default:
                return __('Unusual activity was detected.', 'site-alerts');
        }
    }

    /**
     * Get expanded content for alert type.
     *
     * @param string $type Alert type.
     * @param array $alert Full alert data (optional, used for meta_json extraction).
     * @return array{meaning: string, checks: array, topUrls?: array}
     */
    private function getExpandedContent(string $type, array $alert = []): array
    {
        switch ($type) {
            case 'traffic_drop':
                return [
                    'meaning' => __('Sudden traffic drops are often caused by downtime or recent changes.', 'site-alerts'),
                    'checks'  => [
                        __('Check if your site is currently reachable', 'site-alerts'),
                        __('Review recent plugin or theme changes', 'site-alerts'),
                        __('Look for increases in 404 errors', 'site-alerts'),
                    ],
                ];

            case 'traffic_spike':
                return [
                    'meaning' => __('Traffic spikes can indicate viral content, marketing success, or bot activity.', 'site-alerts'),
                    'checks'  => [
                        __('Check your analytics for traffic sources', 'site-alerts'),
                        __('Review server performance and load times', 'site-alerts'),
                        __('Look for unusual referrer patterns', 'site-alerts'),
                    ],
                ];

            case 'error_404_spike':
                $topUrls = [];

                if (!empty($alert['meta_json'])) {
                    $meta = is_string($alert['meta_json'])
                        ? json_decode($alert['meta_json'], true)
                        : $alert['meta_json'];

                    if (!empty($meta['top']) && is_array($meta['top'])) {
                        foreach ($meta['top'] as $item) {
                            if (is_array($item) && isset($item[0], $item[1])) {
                                $topUrls[] = [
                                    'path'  => (string)$item[0],
                                    'count' => (int)$item[1],
                                ];
                            }
                        }
                    }
                }

                return [
                    'meaning' => __('Missing pages can frustrate visitors and affect SEO.', 'site-alerts'),
                    'checks'  => [
                        __('Add redirects for missing pages', 'site-alerts'),
                        __('Fix internal links pointing to missing URLs', 'site-alerts'),
                        __('Review recent permalink changes', 'site-alerts'),
                    ],
                    'topUrls' => $topUrls,
                ];

            default:
                return [
                    'meaning' => __('This issue may require your attention.', 'site-alerts'),
                    'checks'  => [
                        __('Review recent changes to your site', 'site-alerts'),
                        __('Check your site for any visible issues', 'site-alerts'),
                    ],
                ];
        }
    }

    /**
     * Get 7-day history data.
     *
     * @return array{showTable: bool, average: array|null, rows: array, emptyMessage: string, staleWarning: bool}
     */
    public function getHistory(): array
    {
        $daysWithData = count($this->rawHistory);
        $isStale      = $this->status === PluginStatus::STATUS_ISSUE;

        // Fresh install or no data: show "getting started" message
        if ($this->status === PluginStatus::STATUS_FRESH || $daysWithData === 0) {
            return [
                'showTable'    => false,
                'title'        => __('Getting started', 'site-alerts'),
                'icon'         => 'sa-icon--info',
                'average'      => null,
                'rows'         => [],
                'emptyMessage' => __("We're collecting baseline data. Your first history will appear shortly.", 'site-alerts'),
                'staleWarning' => false,
            ];
        }

        // 1-2 days: not enough for meaningful averages
        if ($daysWithData < 3) {
            return [
                'showTable'    => false,
                'title'        => __('Building history', 'site-alerts'),
                'icon'         => 'sa-icon--traffic',
                'average'      => null,
                'rows'         => [],
                'emptyMessage' => __('Building history — check back in a couple days for meaningful trends.', 'site-alerts'),
                'staleWarning' => false,
            ];
        }

        // 3+ days: show table with averages
        return [
            'showTable'    => true,
            'average'      => $this->calculateHistoryAverage(),
            'rows'         => $this->rawHistory,
            'emptyMessage' => __('No statistics available for the selected period.', 'site-alerts'),
            'staleWarning' => $isStale,
        ];
    }

    /**
     * Calculate average pageviews and 404 errors from history.
     *
     * @return array{pageviews: int, errors_404: int}|null
     */
    private function calculateHistoryAverage(): ?array
    {
        if (empty($this->rawHistory)) {
            return null;
        }

        $totalPageviews = 0;
        $total404       = 0;
        $count          = count($this->rawHistory);

        foreach ($this->rawHistory as $row) {
            $totalPageviews += (int)($row['pageviews'] ?? 0);
            $total404       += (int)($row['errors_404'] ?? 0);
        }

        return [
            'pageviews'  => $count > 0 ? (int)round($totalPageviews / $count) : 0,
            'errors_404' => $count > 0 ? (int)round($total404 / $count) : 0,
        ];
    }

    /**
     * Get the header status line text.
     *
     * @return string
     */
    public function getStatusLine(): string
    {
        switch ($this->status) {
            case PluginStatus::STATUS_FRESH:
                return __('Not checked yet · Monitoring starting', 'site-alerts');
            case PluginStatus::STATUS_LIMITED:
                return __('Collecting data · Insights will appear soon', 'site-alerts');
            case PluginStatus::STATUS_ISSUE:
                return __('Last checked: over 24 hours ago · Check monitoring status', 'site-alerts');
            default:
                return sprintf(
                /* translators: %s: time ago (e.g., "2 minutes") */
                    __('Last checked: %s ago · Range: last 7 days', 'site-alerts'),
                    human_time_diff(PluginStatus::getLastRunTimestamp(), DateTimeUtils::timestamp())
                );
        }
    }
}
