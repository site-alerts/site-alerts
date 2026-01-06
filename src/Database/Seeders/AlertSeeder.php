<?php

namespace SiteAlerts\Database\Seeders;

use SiteAlerts\Abstracts\AbstractSeeder;
use SiteAlerts\Database\Factories\AlertFactory;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AlertSeeder
 *
 * Seeds the alerts table with fake alert data.
 *
 * @package SiteAlerts\Database\Seeders
 * @version 1.0.0
 */
class AlertSeeder extends AbstractSeeder
{
    /**
     * Table name.
     *
     * @var string
     */
    protected string $table = 'alerts';

    /**
     * Seeder priority (runs after DailyStatsSeeder).
     *
     * @var int
     */
    protected int $priority = 10;

    /**
     * Factory instance.
     *
     * @var AlertFactory
     */
    private AlertFactory $factory;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->factory = new AlertFactory();
    }

    /**
     * Run the seeder.
     *
     * @return int Number of records created.
     */
    public function run(): int
    {
        $this->factory->setPattern($this->pattern);

        $this->log("Seeding alerts with '{$this->pattern}' pattern for {$this->days} days...");

        if ($this->pattern === 'alerts') {
            return $this->seedAlerts();
        }

        return $this->seedRealistic();
    }

    /**
     * Seed with realistic pattern.
     *
     * Creates occasional random alerts (~10% of days).
     *
     * @return int Number of records created.
     */
    private function seedRealistic(): int
    {
        $dates = $this->getDateRange();
        $count = 0;

        foreach ($dates as $date) {
            // ~10% chance of alert on any given day
            if (wp_rand(1, 100) <= 10) {
                $alert = $this->factory->randomAlert($date);

                if ($alert !== null) {
                    $count++;
                }
            }
        }

        $this->success("Created {$count} alert records");

        return $count;
    }

    /**
     * Seed with alerts pattern.
     *
     * Creates alerts on specific days to match DailyStatsSeeder pattern:
     * - Day 10: Traffic drop (warning or critical)
     * - Day 20: Traffic spike (info)
     * - Day 25: 404 spike (warning)
     *
     * @return int Number of records created.
     */
    private function seedAlerts(): int
    {
        $dates = $this->getDateRange();
        $count = 0;

        foreach ($dates as $index => $date) {
            $dayIndex = $index + 1;
            $alert    = null;

            // Day 10: Traffic drop
            if ($dayIndex === 10) {
                $percentDrop = wp_rand(35, 55);
                $critical    = $percentDrop >= 40;
                $alert       = $this->factory->trafficDrop($date, $percentDrop, $critical);
            }

            // Day 20: Traffic spike
            if ($dayIndex === 20) {
                $percentIncrease = wp_rand(70, 110);
                $alert           = $this->factory->trafficSpike($date, $percentIncrease);
            }

            // Day 25: 404 spike
            if ($dayIndex === 25) {
                $errorCount = wp_rand(50, 80);
                $average    = wp_rand(10, 15);
                $alert      = $this->factory->error404Spike($date, $errorCount, $average);
            }

            if ($alert !== null) {
                $count++;
            }
        }

        $this->success("Created {$count} alert records");

        return $count;
    }
}
