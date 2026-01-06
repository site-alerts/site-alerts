<?php

namespace SiteAlerts\Cron;

use SiteAlerts\Abstracts\AbstractSingleton;
use SiteAlerts\Utils\Logger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CronManager
 *
 * Manages scheduled tasks/cron jobs.
 *
 * @package SiteAlerts\Cron
 * @version 1.0.0
 */
class CronManager extends AbstractSingleton
{
    /**
     * Registered tasks
     *
     * @var CronTask[]
     */
    private array $tasks = [];

    /**
     * Custom intervals
     *
     * @var array
     */
    private array $intervals = [];

    /**
     * Register hooks.
     *
     * @return void
     */
    public function register(): void
    {
        add_filter('cron_schedules', [$this, 'addIntervals']);

        // Register cron handlers
        $this->registerCronHandlers();

        // Register callbacks and schedule crons on init
        add_action('init', [$this, 'registerCallbacks']);
        add_action('init', [$this, 'scheduleAll']);

        // Also listen to activation event (for future activations)
        add_action('site_alerts_schedule_events', [$this, 'scheduleAll']);
    }

    /**
     * Register cron handlers.
     *
     * @return void
     */
    private function registerCronHandlers(): void
    {
        $dailyHandler = new DailyCronHandler();
        $dailyHandler->register();
    }

    /**
     * Add a custom interval.
     *
     * @param string $name Interval name.
     * @param int $seconds Interval in seconds.
     * @param string $display Display name.
     * @return self
     */
    public function addInterval(string $name, int $seconds, string $display): self
    {
        $this->intervals[$name] = [
            'interval' => $seconds,
            'display'  => $display,
        ];
        return $this;
    }

    /**
     * Add intervals to WordPress cron schedules.
     *
     * @param array $schedules Existing schedules.
     * @return array
     */
    public function addIntervals(array $schedules): array
    {
        // Add common custom intervals
        $defaults = [
            'site_alerts_every_minute'     => [
                'interval' => MINUTE_IN_SECONDS,
                'display'  => __('Every Minute', 'site-alerts'),
            ],
            'site_alerts_every_5_minutes'  => [
                'interval' => 5 * MINUTE_IN_SECONDS,
                'display'  => __('Every 5 Minutes', 'site-alerts'),
            ],
            'site_alerts_every_15_minutes' => [
                'interval' => 15 * MINUTE_IN_SECONDS,
                'display'  => __('Every 15 Minutes', 'site-alerts'),
            ],
            'site_alerts_every_30_minutes' => [
                'interval' => 30 * MINUTE_IN_SECONDS,
                'display'  => __('Every 30 Minutes', 'site-alerts'),
            ],
            'site_alerts_weekly'           => [
                'interval' => WEEK_IN_SECONDS,
                'display'  => __('Once Weekly', 'site-alerts'),
            ],
            'site_alerts_monthly'          => [
                'interval' => MONTH_IN_SECONDS,
                'display'  => __('Once Monthly', 'site-alerts'),
            ],
        ];

        return array_merge($schedules, $defaults, $this->intervals);
    }

    /**
     * Add a task.
     *
     * @param CronTask $task The task to add.
     * @return self
     */
    public function addTask(CronTask $task): self
    {
        $this->tasks[$task->getHook()] = $task;
        return $this;
    }

    /**
     * Create and add a task.
     *
     * @param string $hook Task hook name.
     * @param callable $callback Callback function.
     * @param string $recurrence Recurrence interval.
     * @return CronTask
     */
    public function create(string $hook, callable $callback, string $recurrence = 'hourly'): CronTask
    {
        $task = new CronTask($hook, $callback, $recurrence);
        $this->addTask($task);
        return $task;
    }

    /**
     * Get a task by hook.
     *
     * @param string $hook Task hook name.
     * @return CronTask|null
     */
    public function getTask(string $hook): ?CronTask
    {
        return $this->tasks[$hook] ?? null;
    }

    /**
     * Get all tasks.
     *
     * @return CronTask[]
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }

    /**
     * Remove a task.
     *
     * @param string $hook Task hook name.
     * @return self
     */
    public function removeTask(string $hook): self
    {
        unset($this->tasks[$hook]);
        return $this;
    }

    /**
     * Register task callbacks.
     *
     * @return void
     */
    public function registerCallbacks(): void
    {
        foreach ($this->tasks as $task) {
            add_action($task->getHook(), function () use ($task) {
                $this->executeTask($task);
            });
        }
    }

    /**
     * Schedule all tasks.
     *
     * @return void
     */
    public function scheduleAll(): void
    {
        /**
         * Filter the cron tasks before scheduling.
         *
         * @param CronTask[] $tasks Cron tasks.
         */
        $tasks = apply_filters('site_alerts_cron_tasks', $this->tasks);

        foreach ($tasks as $task) {
            if ($task->isEnabled()) {
                $this->schedule($task);
            }
        }
    }

    /**
     * Schedule a single task.
     *
     * @param CronTask $task The task to schedule.
     * @return bool
     */
    public function schedule(CronTask $task): bool
    {
        if ($task->isScheduled()) {
            return true;
        }

        $result = wp_schedule_event(
            $task->getFirstRun(),
            $task->getRecurrence(),
            $task->getHook(),
            $task->getArgs()
        );

        if ($result) {
            Logger::debug('Cron task scheduled', [
                'hook'       => $task->getHook(),
                'recurrence' => $task->getRecurrence(),
                'first_run'  => gmdate('Y-m-d H:i:s', $task->getFirstRun()),
            ]);
        }

        return $result !== false;
    }

    /**
     * Unschedule a task.
     *
     * @param string $hook Task hook name.
     * @return bool
     */
    public function unschedule(string $hook): bool
    {
        $task = $this->getTask($hook);
        $args = $task ? $task->getArgs() : [];

        $timestamp = wp_next_scheduled($hook, $args);

        if ($timestamp) {
            wp_unschedule_event($timestamp, $hook, $args);

            Logger::debug('Cron task unscheduled', ['hook' => $hook]);

            return true;
        }

        return false;
    }

    /**
     * Unschedule all tasks.
     *
     * @return void
     */
    public function unscheduleAll(): void
    {
        foreach ($this->tasks as $task) {
            $this->unschedule($task->getHook());
        }
    }

    /**
     * Clear all scheduled instances of a hook.
     *
     * @param string $hook Task hook name.
     * @return int Number of events unscheduled.
     */
    public function clearAll(string $hook): int
    {
        return wp_unschedule_hook($hook);
    }

    /**
     * Execute a task.
     *
     * @param CronTask $task The task to execute.
     * @return void
     */
    private function executeTask(CronTask $task): void
    {
        if (!$task->isEnabled()) {
            return;
        }

        $hook = $task->getHook();

        Logger::debug('Executing cron task', ['hook' => $hook]);

        try {
            /**
             * Action fired before a cron task executes.
             *
             * @param CronTask $task The task.
             */
            do_action('site_alerts_before_cron_task', $task);

            call_user_func_array($task->getCallback(), $task->getArgs());

            /**
             * Action fired after a cron task executes.
             *
             * @param CronTask $task The task.
             */
            do_action('site_alerts_after_cron_task', $task);

            Logger::debug('Cron task completed', ['hook' => $hook]);

        } catch (\Exception $e) {
            Logger::error('Cron task failed', [
                'hook'  => $hook,
                'error' => $e->getMessage(),
            ]);

            /**
             * Action fired when a cron task fails.
             *
             * @param CronTask $task The task.
             * @param \Exception $e The exception.
             */
            do_action('site_alerts_cron_task_failed', $task, $e);
        }
    }

    /**
     * Run a task immediately.
     *
     * @param string $hook Task hook name.
     * @return bool
     */
    public function runNow(string $hook): bool
    {
        $task = $this->getTask($hook);

        if ($task === null) {
            return false;
        }

        $this->executeTask($task);

        return true;
    }

    /**
     * Check if a task is scheduled.
     *
     * @param string $hook Task hook name.
     * @return bool
     */
    public function isScheduled(string $hook): bool
    {
        $task = $this->getTask($hook);

        if ($task === null) {
            return wp_next_scheduled($hook) !== false;
        }

        return $task->isScheduled();
    }

    /**
     * Get next scheduled run for a task.
     *
     * @param string $hook Task hook name.
     * @return int|false Timestamp or false.
     */
    public function getNextRun(string $hook)
    {
        $task = $this->getTask($hook);
        $args = $task ? $task->getArgs() : [];

        return wp_next_scheduled($hook, $args);
    }

    /**
     * Get all WordPress cron events.
     *
     * @return array
     */
    public function getAllCronEvents(): array
    {
        return _get_cron_array() ?: [];
    }

    /**
     * Get all plugin cron events.
     *
     * @return array
     */
    public function getPluginCronEvents(): array
    {
        $allEvents    = $this->getAllCronEvents();
        $pluginEvents = [];

        foreach ($this->tasks as $hook => $task) {
            foreach ($allEvents as $timestamp => $crons) {
                if (isset($crons[$hook])) {
                    $pluginEvents[$hook] = [
                        'timestamp'  => $timestamp,
                        'recurrence' => $task->getRecurrence(),
                        'next_run'   => gmdate('Y-m-d H:i:s', $timestamp),
                    ];
                }
            }
        }

        return $pluginEvents;
    }
}
