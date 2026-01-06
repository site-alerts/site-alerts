<?php

namespace SiteAlerts\CLI\Commands;

use SiteAlerts\Database\Seeders\SeederManager;
use WP_CLI;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SeedCommand
 *
 * WP-CLI command for database seeding.
 *
 * @package SiteAlerts\CLI\Commands
 * @version 1.0.0
 */
class SeedCommand
{
    /**
     * Seed the database with fake data for testing.
     *
     * ## OPTIONS
     *
     * [--days=<days>]
     * : Number of days of data to generate.
     * ---
     * default: 30
     * ---
     *
     * [--pattern=<pattern>]
     * : Data pattern to use.
     * ---
     * default: realistic
     * options:
     *   - realistic
     *   - alerts
     * ---
     *
     * [--seeder=<seeder>]
     * : Run only a specific seeder (e.g., 'DailyStats' or 'Alert').
     *
     * ## EXAMPLES
     *
     *     # Seed 30 days of realistic data
     *     wp site-alerts seed
     *
     *     # Seed 60 days of data designed to trigger alerts
     *     wp site-alerts seed --days=60 --pattern=alerts
     *
     *     # Run only the DailyStats seeder
     *     wp site-alerts seed --seeder=DailyStats
     *
     *     # Seed only alerts with 90 days of data
     *     wp site-alerts seed --seeder=Alert --days=90
     *
     * @param array $args Positional arguments.
     * @param array $assocArgs Associative arguments.
     * @return void
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        $options = $this->validateOptions($assocArgs);

        WP_CLI::log('');
        WP_CLI::log('Site Alerts Database Seeder');
        WP_CLI::log('===========================');
        WP_CLI::log('');

        $manager = SeederManager::getInstance();

        // Show configuration
        WP_CLI::log("Pattern: {$options['pattern']}");
        WP_CLI::log("Days: {$options['days']}");

        if (!empty($options['seeder'])) {
            WP_CLI::log("Seeder: {$options['seeder']}");
        }

        WP_CLI::log('');

        $startTime = microtime(true);

        if (!empty($options['seeder'])) {
            $count = $manager->run($options['seeder'], $options);

            if ($count === null) {
                $available = implode(', ', $manager->getAvailableSeederNames());
                WP_CLI::error("Seeder '{$options['seeder']}' not found. Available: {$available}");

                return;
            }

            WP_CLI::log("Records created: {$count}");
        } else {
            $results = $manager->runAll($options);

            $totalRecords = array_sum($results);
            WP_CLI::log('');
            WP_CLI::log("Total records created: {$totalRecords}");
        }

        $elapsed = round(microtime(true) - $startTime, 2);

        WP_CLI::log('');
        WP_CLI::success("Seeding completed in {$elapsed}s");
    }

    /**
     * Validate command options.
     *
     * @param array $assocArgs Associative arguments.
     * @return array Validated options.
     */
    private function validateOptions(array $assocArgs): array
    {
        $days = isset($assocArgs['days']) ? (int)$assocArgs['days'] : 30;
        $days = max(1, min(365, $days));

        $pattern = $assocArgs['pattern'] ?? 'realistic';

        if (!in_array($pattern, ['realistic', 'alerts'], true)) {
            WP_CLI::warning("Invalid pattern '{$pattern}', using 'realistic'");
            $pattern = 'realistic';
        }

        return [
            'days'    => $days,
            'pattern' => $pattern,
            'seeder'  => $assocArgs['seeder'] ?? '',
        ];
    }
}
