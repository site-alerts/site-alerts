<?php

namespace SiteAlerts\Cron;

use SiteAlerts\Abstracts\AbstractCronTask;
use SiteAlerts\Services\Cron\DailyStatsFlusher;
use SiteAlerts\Utils\Logger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class DailyCronHandler
 *
 * Central cron handler that runs daily at midnight.
 * Executes all registered daily services sequentially.
 *
 * @package SiteAlerts\Cron
 * @version 1.0.0
 */
class DailyCronHandler extends AbstractCronTask
{
    /**
     * Task hook name
     *
     * @var string
     */
    protected string $hook = 'site_alerts_daily_cron';

    /**
     * Recurrence interval
     *
     * @var string
     */
    protected string $recurrence = 'daily';

    /**
     * Services to run daily.
     * Each service must have getInstance() and run() methods.
     *
     * @var array<class-string>
     */
    private array $services = [
        DailyStatsFlusher::class,
    ];

    /**
     * Configure the cron task.
     *
     * @param CronTask $task The task to configure.
     * @return void
     */
    protected function configure(CronTask $task): void
    {
        $task->startTodayAt(0, 0); // Midnight
    }

    /**
     * Execute all registered daily services.
     *
     * @return void
     */
    public function handle(): void
    {
        /**
         * Filter the daily cron services before execution.
         *
         * @param array $services Array of service class names.
         */
        $services = apply_filters('site_alerts_daily_cron_services', $this->services);

        Logger::debug('Daily cron started', ['service_count' => count($services)]);

        /**
         * Fires before any daily cron services run.
         */
        do_action('site_alerts_daily_cron_before_dispatch');

        $results = [];

        foreach ($services as $serviceClass) {
            $result                 = $this->executeService($serviceClass);
            $results[$serviceClass] = $result;
        }

        /**
         * Fires after all daily cron services complete.
         *
         * @param array $results Array of service results [class => ['success' => bool, 'duration' => float]].
         */
        do_action('site_alerts_daily_cron_after_dispatch', $results);

        Logger::debug('Daily cron completed', ['results' => $results]);
    }

    /**
     * Execute a single service with error handling.
     *
     * @param class-string $serviceClass The service class name.
     * @return array{success: bool, duration: float, error?: string}
     */
    private function executeService(string $serviceClass): array
    {
        $serviceId = $this->getServiceId($serviceClass);

        /**
         * Filter whether a specific service should run.
         *
         * @param bool $enabled Whether the service is enabled.
         * @param string $serviceId The service identifier.
         */
        $enabled = apply_filters('site_alerts_daily_cron_service_enabled', true, $serviceId);

        if (!$enabled) {
            Logger::debug("Service skipped (disabled): {$serviceId}");
            return ['success' => true, 'duration' => 0, 'skipped' => true];
        }

        if (!class_exists($serviceClass)) {
            Logger::error("Service class not found: {$serviceClass}");
            return ['success' => false, 'duration' => 0, 'error' => 'Class not found'];
        }

        try {
            /**
             * Fires before a daily cron service executes.
             *
             * @param string $serviceId The service identifier.
             */
            do_action('site_alerts_daily_cron_before_service', $serviceId);

            $startTime = microtime(true);
            $serviceClass::getInstance()->run();
            $duration = microtime(true) - $startTime;

            /**
             * Fires after a daily cron service completes successfully.
             *
             * @param string $serviceId The service identifier.
             * @param bool $success Whether the service succeeded.
             * @param float $duration Execution time in seconds.
             */
            do_action('site_alerts_daily_cron_after_service', $serviceId, true, $duration);

            Logger::debug("Service completed: {$serviceId}", ['duration' => round($duration, 4)]);

            return ['success' => true, 'duration' => $duration];
        } catch (\Throwable $e) {
            $duration = microtime(true) - ($startTime ?? microtime(true));

            Logger::error("Service failed: {$serviceId}", [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);

            /**
             * Fires after a daily cron service fails.
             *
             * @param string $serviceId The service identifier.
             * @param bool $success Whether the service succeeded.
             * @param float $duration Execution time in seconds.
             */
            do_action('site_alerts_daily_cron_after_service', $serviceId, false, $duration);

            return ['success' => false, 'duration' => $duration, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get a readable service identifier from class name.
     *
     * @param string $serviceClass The fully qualified class name.
     * @return string
     */
    private function getServiceId(string $serviceClass): string
    {
        return basename(str_replace('\\', '/', $serviceClass));
    }

    /**
     * Get registered services.
     *
     * @return array<class-string>
     */
    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * Add a service to the daily cron.
     *
     * @param class-string $serviceClass The service class name.
     * @return self
     */
    public function addService(string $serviceClass): self
    {
        if (!in_array($serviceClass, $this->services, true)) {
            $this->services[] = $serviceClass;
        }
        return $this;
    }

    /**
     * Remove a service from the daily cron.
     *
     * @param class-string $serviceClass The service class name.
     * @return self
     */
    public function removeService(string $serviceClass): self
    {
        $this->services = array_filter(
            $this->services,
            static fn($service) => $service !== $serviceClass
        );
        return $this;
    }
}
