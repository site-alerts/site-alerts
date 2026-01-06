<?php

namespace SiteAlerts\Abstracts;

use SiteAlerts\Cron\CronManager;
use SiteAlerts\Cron\CronTask;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AbstractCronTask
 *
 * Base class for creating cron tasks with OOP pattern.
 *
 * @package SiteAlerts\Abstracts
 * @version 1.0.0
 */
abstract class AbstractCronTask
{
    /**
     * Task hook name (must be unique)
     *
     * @var string
     */
    protected string $hook;

    /**
     * Recurrence interval
     *
     * @var string
     */
    protected string $recurrence = 'hourly';

    /**
     * Whether task is enabled
     *
     * @var bool
     */
    protected bool $enabled = true;

    /**
     * Cron task instance
     *
     * @var CronTask|null
     */
    protected ?CronTask $task = null;

    /**
     * Register the cron task.
     *
     * @return void
     */
    public function register(): void
    {
        $this->task = CronTask::make($this->hook, [$this, 'handle'], $this->recurrence);

        if (!$this->enabled) {
            $this->task->disable();
        }

        $this->configure($this->task);

        CronManager::getInstance()->addTask($this->task);
    }

    /**
     * Configure the cron task.
     * Override this method to customize the task.
     *
     * @param CronTask $task The task to configure.
     * @return void
     */
    protected function configure(CronTask $task): void
    {
        // Override in child class for additional configuration
    }

    /**
     * Handle the cron task execution.
     * Override this method with your task logic.
     *
     * @return void
     */
    abstract public function handle(): void;

    /**
     * Get the task hook name.
     *
     * @return string
     */
    public function getHook(): string
    {
        return $this->hook;
    }

    /**
     * Get the recurrence interval.
     *
     * @return string
     */
    public function getRecurrence(): string
    {
        return $this->recurrence;
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
     * Get the cron task instance.
     *
     * @return CronTask|null
     */
    public function getTask(): ?CronTask
    {
        return $this->task;
    }

    /**
     * Check if task is scheduled.
     *
     * @return bool
     */
    public function isScheduled(): bool
    {
        return $this->task && $this->task->isScheduled();
    }

    /**
     * Get next scheduled run.
     *
     * @return int|false
     */
    public function getNextRun()
    {
        return $this->task ? $this->task->getNextRun() : false;
    }

    /**
     * Run the task immediately.
     *
     * @return void
     */
    public function runNow(): void
    {
        $this->handle();
    }

    /**
     * Schedule the task.
     *
     * @return bool
     */
    public function schedule(): bool
    {
        if ($this->task === null) {
            return false;
        }

        return CronManager::getInstance()->schedule($this->task);
    }

    /**
     * Unschedule the task.
     *
     * @return bool
     */
    public function unschedule(): bool
    {
        return CronManager::getInstance()->unschedule($this->hook);
    }
}
