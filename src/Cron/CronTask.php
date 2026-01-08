<?php

namespace SiteAlerts\Cron;

use SiteAlerts\Utils\DateTimeUtils;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CronTask
 *
 * Represents a single cron task configuration.
 *
 * @package SiteAlerts\Cron
 * @version 1.0.0
 */
class CronTask
{
    /**
     * Task hook name
     *
     * @var string
     */
    private string $hook;

    /**
     * Callback function
     *
     * @var callable
     */
    private $callback; // Mixed type: callable

    /**
     * Recurrence interval
     *
     * @var string
     */
    private string $recurrence;

    /**
     * First run timestamp
     *
     * @var int
     */
    private int $firstRun;

    /**
     * Arguments to pass to callback
     *
     * @var array
     */
    private array $args = [];

    /**
     * Whether task is enabled
     *
     * @var bool
     */
    private bool $enabled = true;

    /**
     * CronTask constructor.
     *
     * @param string $hook Task hook name.
     * @param callable $callback Callback function.
     * @param string $recurrence Recurrence interval.
     */
    public function __construct(string $hook, callable $callback, string $recurrence = 'hourly')
    {
        $this->hook       = $hook;
        $this->callback   = $callback;
        $this->recurrence = $recurrence;
        $this->firstRun   = DateTimeUtils::timestamp();
    }

    /**
     * Create a new cron task.
     *
     * @param string $hook Task hook name.
     * @param callable $callback Callback function.
     * @param string $recurrence Recurrence interval.
     * @return self
     */
    public static function make(string $hook, callable $callback, string $recurrence = 'hourly'): self
    {
        return new self($hook, $callback, $recurrence);
    }

    /**
     * Set the recurrence interval.
     *
     * @param string $recurrence Recurrence interval.
     * @return self
     */
    public function recurrence(string $recurrence): self
    {
        $this->recurrence = $recurrence;
        return $this;
    }

    /**
     * Set hourly recurrence.
     *
     * @return self
     */
    public function hourly(): self
    {
        $this->recurrence = 'hourly';
        return $this;
    }

    /**
     * Set twice daily recurrence.
     *
     * @return self
     */
    public function twiceDaily(): self
    {
        $this->recurrence = 'twicedaily';
        return $this;
    }

    /**
     * Set daily recurrence.
     *
     * @return self
     */
    public function daily(): self
    {
        $this->recurrence = 'daily';
        return $this;
    }

    /**
     * Set weekly recurrence.
     *
     * @return self
     */
    public function weekly(): self
    {
        $this->recurrence = 'weekly';
        return $this;
    }

    /**
     * Set the first run time.
     *
     * @param int $timestamp Unix timestamp.
     * @return self
     */
    public function startAt(int $timestamp): self
    {
        $this->firstRun = $timestamp;
        return $this;
    }

    /**
     * Set first run to now.
     *
     * @return self
     */
    public function startNow(): self
    {
        $this->firstRun = DateTimeUtils::timestamp();
        return $this;
    }

    /**
     * Set first run to a specific time today (WordPress timezone aware).
     *
     * @param int $hour Hour (0-23).
     * @param int $minute Minute (0-59).
     * @return self
     */
    public function startTodayAt(int $hour, int $minute = 0): self
    {
        $timezone    = DateTimeUtils::getTimezone();
        $now         = current_datetime();
        $todayTarget = $now->setTime($hour, $minute, 0);

        if ($todayTarget->getTimestamp() < $now->getTimestamp()) {
            $todayTarget = $todayTarget->modify('+1 day');
        }

        $this->firstRun = $todayTarget->getTimestamp();

        return $this;
    }

    /**
     * Set callback arguments.
     *
     * @param array $args Arguments to pass to callback.
     * @return self
     */
    public function args(array $args): self
    {
        $this->args = $args;
        return $this;
    }

    /**
     * Enable the task.
     *
     * @return self
     */
    public function enable(): self
    {
        $this->enabled = true;
        return $this;
    }

    /**
     * Disable the task.
     *
     * @return self
     */
    public function disable(): self
    {
        $this->enabled = false;
        return $this;
    }

    /**
     * Get the hook name.
     *
     * @return string
     */
    public function getHook(): string
    {
        return $this->hook;
    }

    /**
     * Get the callback.
     *
     * @return callable
     */
    public function getCallback(): callable
    {
        return $this->callback;
    }

    /**
     * Get the recurrence.
     *
     * @return string
     */
    public function getRecurrence(): string
    {
        return $this->recurrence;
    }

    /**
     * Get the first run timestamp.
     *
     * @return int
     */
    public function getFirstRun(): int
    {
        return $this->firstRun;
    }

    /**
     * Get the callback arguments.
     *
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * Check if task is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Check if task is scheduled.
     *
     * @return bool
     */
    public function isScheduled(): bool
    {
        return wp_next_scheduled($this->hook, $this->args) !== false;
    }

    /**
     * Get next scheduled run.
     *
     * @return int|false
     */
    public function getNextRun()
    {
        return wp_next_scheduled($this->hook, $this->args);
    }
}
