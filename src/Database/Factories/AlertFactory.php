<?php

namespace SiteAlerts\Database\Factories;

use SiteAlerts\Abstracts\AbstractFactory;
use SiteAlerts\Models\Alert;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AlertFactory
 *
 * Factory for creating Alert records with fake data.
 *
 * @package SiteAlerts\Database\Factories
 * @version 1.0.0
 */
class AlertFactory extends AbstractFactory
{
    /**
     * Model class.
     *
     * @var string
     */
    protected string $model = Alert::class;

    /**
     * Alert type configurations.
     *
     * @var array
     */
    private array $alertTypes = [
        'traffic_drop'    => [
            'severities' => ['warning', 'critical'],
            'title'      => 'Traffic dropped by %d%%',
            'message'    => 'Today\'s traffic is significantly lower than your 7-day average. This could indicate technical issues, SEO problems, or external factors affecting your site visibility.',
        ],
        'traffic_spike'   => [
            'severities' => ['info'],
            'title'      => 'Traffic increased by %d%%',
            'message'    => 'Your traffic is significantly higher than your 7-day average. This could be due to viral content, marketing campaigns, or external links to your site.',
        ],
        'error_404_spike' => [
            'severities' => ['warning'],
            'title'      => '404 errors increased significantly',
            'message'    => 'Your site is receiving more 404 errors than usual. This may indicate broken links, removed pages, or potential crawling issues.',
        ],
    ];

    /**
     * Define default attributes.
     *
     * @return array
     */
    protected function definition(): array
    {
        return [
            'alert_date' => current_time('Y-m-d'),
            'type'       => 'traffic_drop',
            'severity'   => 'warning',
            'title'      => 'Traffic dropped by 35%',
            'message'    => $this->alertTypes['traffic_drop']['message'],
            'meta_json'  => null,
        ];
    }

    /**
     * Create a traffic drop alert.
     *
     * @param string $date Date in Y-m-d format.
     * @param int $percentDrop Percentage drop (positive number).
     * @param bool $critical Whether it's critical severity.
     * @return Alert|null
     */
    public function trafficDrop(string $date, int $percentDrop = 35, bool $critical = false): ?Alert
    {
        $severity = $critical ? 'critical' : 'warning';
        $today    = $this->randomInt(300, 600);
        $avg7     = (int)($today / (1 - ($percentDrop / 100)));

        $metaJson = wp_json_encode([
            'today'      => $today,
            'avg7'       => $avg7,
            'change_pct' => -$percentDrop,
        ]);

        // Delete existing to avoid duplicate key error
        Alert::deleteByDateAndType($date, 'traffic_drop');

        $result = $this->create([
            'alert_date' => $date,
            'type'       => 'traffic_drop',
            'severity'   => $severity,
            'title'      => sprintf($this->alertTypes['traffic_drop']['title'], $percentDrop),
            'message'    => $this->alertTypes['traffic_drop']['message'],
            'meta_json'  => $metaJson,
        ]);

        return $result instanceof Alert ? $result : null;
    }

    /**
     * Create a traffic spike alert.
     *
     * @param string $date Date in Y-m-d format.
     * @param int $percentIncrease Percentage increase.
     * @return Alert|null
     */
    public function trafficSpike(string $date, int $percentIncrease = 75): ?Alert
    {
        $avg7  = $this->randomInt(800, 1200);
        $today = (int)($avg7 * (1 + ($percentIncrease / 100)));

        $metaJson = wp_json_encode([
            'today'      => $today,
            'avg7'       => $avg7,
            'change_pct' => $percentIncrease,
        ]);

        // Delete existing to avoid duplicate key error
        Alert::deleteByDateAndType($date, 'traffic_spike');

        $result = $this->create([
            'alert_date' => $date,
            'type'       => 'traffic_spike',
            'severity'   => 'info',
            'title'      => sprintf($this->alertTypes['traffic_spike']['title'], $percentIncrease),
            'message'    => $this->alertTypes['traffic_spike']['message'],
            'meta_json'  => $metaJson,
        ]);

        return $result instanceof Alert ? $result : null;
    }

    /**
     * Create a 404 spike alert.
     *
     * @param string $date Date in Y-m-d format.
     * @param int $errorCount Today's 404 count.
     * @param int $average Average 404 count.
     * @return Alert|null
     */
    public function error404Spike(string $date, int $errorCount = 50, int $average = 15): ?Alert
    {
        $changePct = (int)(($errorCount - $average) / $average * 100);

        $topPaths = [
            ['/wp-login.php', $this->randomInt(10, 20)],
            ['/xmlrpc.php', $this->randomInt(5, 15)],
            ['/.env', $this->randomInt(3, 10)],
        ];

        $metaJson = wp_json_encode([
            'today'      => $errorCount,
            'avg7'       => $average,
            'change_pct' => $changePct,
            'top'        => $topPaths,
        ]);

        // Delete existing to avoid duplicate key error
        Alert::deleteByDateAndType($date, 'error_404_spike');

        $result = $this->create([
            'alert_date' => $date,
            'type'       => 'error_404_spike',
            'severity'   => 'warning',
            'title'      => $this->alertTypes['error_404_spike']['title'],
            'message'    => $this->alertTypes['error_404_spike']['message'],
            'meta_json'  => $metaJson,
        ]);

        return $result instanceof Alert ? $result : null;
    }

    /**
     * Create a random alert for realistic pattern.
     *
     * @param string $date Date in Y-m-d format.
     * @return Alert|null
     */
    public function randomAlert(string $date): ?Alert
    {
        $types = ['traffic_drop', 'traffic_spike', 'error_404_spike'];
        $type  = $this->randomElement($types);

        switch ($type) {
            case 'traffic_drop':
                $percentDrop = $this->randomInt(30, 50);
                $critical    = $percentDrop >= 40;

                return $this->trafficDrop($date, $percentDrop, $critical);

            case 'traffic_spike':
                $percentIncrease = $this->randomInt(60, 120);

                return $this->trafficSpike($date, $percentIncrease);

            case 'error_404_spike':
                $errorCount = $this->randomInt(40, 80);
                $average    = $this->randomInt(10, 20);

                return $this->error404Spike($date, $errorCount, $average);

            default:
                return null;
        }
    }
}
