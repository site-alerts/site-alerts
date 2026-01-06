<?php

namespace SiteAlerts\Abstracts;

use SiteAlerts\Database\DatabaseManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AbstractSeeder
 *
 * Base class for database seeders.
 *
 * @package SiteAlerts\Abstracts
 * @version 1.0.0
 */
abstract class AbstractSeeder
{
    /**
     * The table name this seeder operates on (without prefix).
     *
     * @var string
     */
    protected string $table = '';

    /**
     * Seeder priority (lower runs first).
     *
     * @var int
     */
    protected int $priority = 10;

    /**
     * Current seeding pattern.
     *
     * @var string
     */
    protected string $pattern = 'realistic';

    /**
     * Number of days to seed.
     *
     * @var int
     */
    protected int $days = 30;

    /**
     * Set the seeding pattern.
     *
     * @param string $pattern Pattern name ('realistic' or 'alerts').
     * @return self
     */
    public function setPattern(string $pattern): self
    {
        $this->pattern = $pattern;

        return $this;
    }

    /**
     * Get the seeding pattern.
     *
     * @return string
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * Set the number of days to seed.
     *
     * @param int $days Number of days.
     * @return self
     */
    public function setDays(int $days): self
    {
        $this->days = max(1, $days);

        return $this;
    }

    /**
     * Get the number of days to seed.
     *
     * @return int
     */
    public function getDays(): int
    {
        return $this->days;
    }

    /**
     * Run the seeder.
     *
     * @return int Number of records created.
     */
    abstract public function run(): int;

    /**
     * Clean existing data before seeding.
     *
     * @return int Number of records deleted.
     */
    public function clean(): int
    {
        if (empty($this->table)) {
            return 0;
        }

        $count = DatabaseManager::getRowCount($this->table);
        DatabaseManager::truncateTable($this->table);

        $this->log("Truncated {$this->table}: {$count} records removed");

        return $count;
    }

    /**
     * Get the seeder priority.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Get the table name.
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get date range for seeding.
     *
     * @return array Array of dates in Y-m-d format.
     */
    protected function getDateRange(): array
    {
        $dates   = [];
        $endDate = current_time('Y-m-d');
        $endTs   = strtotime($endDate);

        for ($i = $this->days - 1; $i >= 0; $i--) {
            $dates[] = gmdate('Y-m-d', strtotime("-{$i} days", $endTs));
        }

        return $dates;
    }

    /**
     * Log progress message.
     *
     * Uses WP_CLI if available, otherwise logs to debug.
     *
     * @param string $message Message to log.
     * @return void
     */
    protected function log(string $message): void
    {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::log($message);
        }
    }

    /**
     * Log success message.
     *
     * @param string $message Message to log.
     * @return void
     */
    protected function success(string $message): void
    {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::success($message);
        }
    }

    /**
     * Log warning message.
     *
     * @param string $message Message to log.
     * @return void
     */
    protected function warning(string $message): void
    {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::warning($message);
        }
    }
}
