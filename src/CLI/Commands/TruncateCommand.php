<?php

namespace SiteAlerts\CLI\Commands;

use SiteAlerts\Database\Seeders\SeederManager;
use WP_CLI;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TruncateCommand
 *
 * WP-CLI command for truncating database tables.
 *
 * @package SiteAlerts\CLI\Commands
 * @version 1.0.0
 */
class TruncateCommand
{
    /**
     * Truncate database tables.
     *
     * Clears all data from plugin tables without inserting new records.
     *
     * ## OPTIONS
     *
     * [--seeder=<seeder>]
     * : Truncate only a specific table (e.g., 'DailyStats' or 'Alert').
     *
     * [--yes]
     * : Skip confirmation prompt.
     *
     * ## EXAMPLES
     *
     *     # Truncate all tables
     *     wp site-alerts truncate
     *
     *     # Truncate only alerts table
     *     wp site-alerts truncate --seeder=Alert
     *
     *     # Truncate only stats table
     *     wp site-alerts truncate --seeder=DailyStats
     *
     *     # Skip confirmation
     *     wp site-alerts truncate --yes
     *
     * @param array $args Positional arguments.
     * @param array $assocArgs Associative arguments.
     * @return void
     * @throws \ReflectionException
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        $manager          = SeederManager::getInstance();
        $seeder           = $assocArgs['seeder'] ?? '';
        $skipConfirmation = isset($assocArgs['yes']);

        WP_CLI::log('');
        WP_CLI::log('Site Alerts Database Truncate');
        WP_CLI::log('==============================');
        WP_CLI::log('');

        // Validate seeder if specified
        if (!empty($seeder)) {
            $available = $manager->getAvailableSeederNames();

            if (!in_array($seeder, $available, true) && !in_array(ucfirst($seeder), $available, true)) {
                $availableList = implode(', ', $available);
                WP_CLI::error("Seeder '{$seeder}' not found. Available: {$availableList}");

                return;
            }

            $target = "'{$seeder}' table";
        } else {
            $target = 'all plugin tables';
        }

        // Confirmation prompt
        if (!$skipConfirmation) {
            WP_CLI::confirm("Are you sure you want to truncate {$target}? This cannot be undone.");
        }

        $startTime = microtime(true);

        if (!empty($seeder)) {
            $this->truncateSpecific($manager, $seeder);
        } else {
            $this->truncateAll($manager);
        }

        $elapsed = round(microtime(true) - $startTime, 2);

        WP_CLI::log('');
        WP_CLI::success("Truncation completed in {$elapsed}s");
    }

    /**
     * Truncate a specific table.
     *
     * @param SeederManager $manager Seeder manager instance.
     * @param string $seederName Short seeder name.
     * @return void
     */
    private function truncateSpecific(SeederManager $manager, string $seederName): void
    {
        // Use the manager's run method with fresh option to trigger clean
        // But we need direct access to the seeder's clean method
        $seeders = $manager->getSeeders();

        foreach ($seeders as $class) {
            if (!class_exists($class)) {
                continue;
            }

            $className      = (new \ReflectionClass($class))->getShortName();
            $classShortName = str_replace('Seeder', '', $className);

            if (strtolower($classShortName) === strtolower($seederName)) {
                $seeder  = new $class();
                $deleted = $seeder->clean();

                WP_CLI::log("Truncated {$classShortName}: {$deleted} records deleted");

                return;
            }
        }
    }

    /**
     * Truncate all tables.
     *
     * @param SeederManager $manager Seeder manager instance.
     * @return void
     * @throws \ReflectionException
     */
    private function truncateAll(SeederManager $manager): void
    {
        $results = $manager->cleanAll();

        foreach ($results as $class => $deleted) {
            $className = (new \ReflectionClass($class))->getShortName();
            $shortName = str_replace('Seeder', '', $className);

            WP_CLI::log("Truncated {$shortName}: {$deleted} records deleted");
        }
    }
}
