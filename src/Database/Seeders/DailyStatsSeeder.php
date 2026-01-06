<?php

namespace SiteAlerts\Database\Seeders;

use SiteAlerts\Abstracts\AbstractSeeder;
use SiteAlerts\Database\Factories\DailyStatsFactory;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class DailyStatsSeeder
 *
 * Seeds the daily_stats table with fake traffic data.
 *
 * @package SiteAlerts\Database\Seeders
 * @version 1.0.0
 */
class DailyStatsSeeder extends AbstractSeeder
{
    /**
     * Table name.
     *
     * @var string
     */
    protected string $table = 'daily_stats';

    /**
     * Seeder priority (runs first, alerts depend on stats).
     *
     * @var int
     */
    protected int $priority = 5;

    /**
     * Factory instance.
     *
     * @var DailyStatsFactory
     */
    private DailyStatsFactory $factory;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->factory = new DailyStatsFactory();
    }

    /**
     * Run the seeder.
     *
     * @return int Number of records created.
     */
    public function run(): int
    {
        $this->factory->setPattern($this->pattern);

        $this->log("Seeding daily_stats with '{$this->pattern}' pattern for {$this->days} days...");

        $dates = $this->getDateRange();
        $count = 0;

        foreach ($dates as $index => $date) {
            $dayIndex = $index + 1;
            $record   = $this->factory->forDate($date, $dayIndex);

            if ($record !== null) {
                $count++;
            }
        }

        $this->success("Created {$count} daily_stats records");

        return $count;
    }
}
