<?php

namespace SiteAlerts\Database\Factories;

use SiteAlerts\Abstracts\AbstractFactory;
use SiteAlerts\Models\DailyStats;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class DailyStatsFactory
 *
 * Factory for creating DailyStats records with fake data.
 *
 * @package SiteAlerts\Database\Factories
 * @version 1.0.0
 */
class DailyStatsFactory extends AbstractFactory
{
    /**
     * Model class.
     *
     * @var string
     */
    protected string $model = DailyStats::class;

    /**
     * Common 404 paths for realistic data.
     *
     * @var array
     */
    private array $common404Paths = [
        '/wp-login.php',
        '/wp-admin/',
        '/xmlrpc.php',
        '/.env',
        '/wp-content/uploads/2024/missing-image.jpg',
        '/old-page-slug/',
        '/products/discontinued-item/',
        '/blog/deleted-post/',
        '/api/v1/deprecated/',
        '/favicon.ico',
        '/.git/config',
        '/wp-config.php.bak',
        '/backup.sql',
        '/admin.php',
        '/login',
    ];

    /**
     * Define default attributes.
     *
     * @return array
     */
    protected function definition(): array
    {
        return [
            'stats_date'   => current_time('Y-m-d'),
            'pageviews'    => $this->randomInt(800, 1500),
            'errors_404'   => $this->randomInt(5, 30),
            'top_404_json' => null,
        ];
    }

    /**
     * Create stats for a specific date with pattern-aware data.
     *
     * @param string $date Date in Y-m-d format.
     * @param int $dayIndex Day index in sequence (for alerts pattern).
     * @return DailyStats|null
     */
    public function forDate(string $date, int $dayIndex = 0): ?DailyStats
    {
        if ($this->pattern === 'alerts') {
            $attributes = $this->alertsPattern($date, $dayIndex);
        } else {
            $attributes = $this->realisticPattern($date);
        }

        // Delete existing record to avoid duplicate key error when re-seeding
        DailyStats::deleteByDate($date);

        $result = $this->create($attributes);

        return $result instanceof DailyStats ? $result : null;
    }

    /**
     * Create realistic pattern data.
     *
     * Generates natural variance: 800-1500 pageviews, 5-30 404s.
     * Weekend traffic is reduced by 20-40%.
     *
     * @param string $date Date in Y-m-d format.
     * @return array Attributes for realistic pattern.
     */
    public function realisticPattern(string $date): array
    {
        $basePageviews = $this->randomInt(800, 1500);
        $pageviews     = $this->applyDayOfWeekVariance($date, $basePageviews);

        $errors404  = $this->randomInt(5, 30);
        $top404Json = $this->generateTop404Json($errors404);

        return [
            'stats_date'   => $date,
            'pageviews'    => $pageviews,
            'errors_404'   => $errors404,
            'top_404_json' => $top404Json,
        ];
    }

    /**
     * Create alerts pattern data.
     *
     * Generates data designed to trigger alerts on specific days:
     * - Day 10: Traffic drop (< 70% of average)
     * - Day 20: Traffic spike (> 150% of average)
     * - Day 25: 404 spike (> 200% of average)
     *
     * @param string $date Date in Y-m-d format.
     * @param int $dayIndex Day index in sequence (1-based).
     * @return array Attributes for alerts pattern.
     */
    public function alertsPattern(string $date, int $dayIndex): array
    {
        $baselinePageviews = $this->randomInt(1000, 1200);
        $baseline404       = $this->randomInt(10, 15);

        $pageviews = $baselinePageviews;
        $errors404 = $baseline404;

        // Day 10: Traffic drop (triggers warning/critical)
        if ($dayIndex === 10) {
            $pageviews = $this->randomInt(300, 500);
        }

        // Day 20: Traffic spike (triggers info)
        if ($dayIndex === 20) {
            $pageviews = $this->randomInt(2000, 2500);
        }

        // Day 25: 404 spike (triggers warning)
        if ($dayIndex === 25) {
            $errors404 = $this->randomInt(50, 80);
        }

        $top404Json = $this->generateTop404Json($errors404);

        return [
            'stats_date'   => $date,
            'pageviews'    => $pageviews,
            'errors_404'   => $errors404,
            'top_404_json' => $top404Json,
        ];
    }

    /**
     * Generate top 404 JSON data.
     *
     * @param int $errorCount Number of 404 errors.
     * @return string|null JSON string or null.
     */
    private function generateTop404Json(int $errorCount): ?string
    {
        if ($errorCount < 3) {
            return null;
        }

        $pathCount = $this->randomInt(1, 3);
        $paths     = $this->randomElements($this->common404Paths, $pathCount);

        $remaining = $errorCount;
        $top404    = [];

        foreach ($paths as $index => $path) {
            if ($index === count($paths) - 1) {
                $count = $remaining;
            } else {
                $count     = $this->randomInt(1, (int)($remaining * 0.6));
                $remaining -= $count;
            }

            if ($count > 0) {
                $top404[] = [$path, $count];
            }
        }

        usort($top404, static function ($a, $b) {
            return $b[1] - $a[1];
        });

        return wp_json_encode(array_slice($top404, 0, 3));
    }

    /**
     * Apply day-of-week variance to pageviews.
     *
     * Weekends typically have less traffic.
     *
     * @param string $date Date in Y-m-d format.
     * @param int $basePageviews Base pageview count.
     * @return int Adjusted pageviews.
     */
    private function applyDayOfWeekVariance(string $date, int $basePageviews): int
    {
        if ($this->isWeekend($date)) {
            $reduction = $this->randomFloat(0.20, 0.40);

            return (int)($basePageviews * (1 - $reduction));
        }

        return $basePageviews;
    }
}
