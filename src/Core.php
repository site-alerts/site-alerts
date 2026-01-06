<?php

namespace SiteAlerts;

use SiteAlerts\Admin\AdminNotices;
use SiteAlerts\Admin\PromoBanner;
use SiteAlerts\AdminUI\Theme\ThemeManager;
use SiteAlerts\Cache\CacheManager;
use SiteAlerts\CLI\CLIManager;
use SiteAlerts\Cron\CronManager;
use SiteAlerts\Database\SchemaManager;
use SiteAlerts\Menu\MenuManager;
use SiteAlerts\Utils\Logger;
use SiteAlerts\Services\Admin\Alerts\AlertsManager;
use SiteAlerts\AdminUI\Assets\AdminUIAssets;
use SiteAlerts\Services\Frontend\Traffic\TrafficManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Core
 *
 * Main plugin bootstrap class (Singleton pattern).
 *
 * @package SiteAlerts
 * @version 1.0.0
 */
final class Core
{
    /**
     * The single instance of the class
     *
     * @var Core|null
     */
    private static ?Core $instance = null;

    /**
     * Core manager services (always loaded)
     *
     * @var array
     */
    private array $coreServices = [
        SchemaManager::class,
        CacheManager::class,
        CronManager::class,
        CLIManager::class,
    ];

    /**
     * List of admin-specific service classes
     *
     * @var array
     */
    private array $adminServices = [
        MenuManager::class,
        AlertsManager::class,
    ];

    /**
     * List of frontend-specific service classes
     *
     * @var array
     */
    private array $frontendServices = [
        TrafficManager::class,
    ];

    /**
     * Get the instance via lazy initialization (created on first usage)
     *
     * @return Core
     */
    public static function getInstance(): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Cloning is not allowed.
     *
     * @return void
     */
    protected function __clone()
    {
        _doing_it_wrong(__FUNCTION__, esc_html__('Cloning is not allowed.', 'site-alerts'), '1.0.0');
    }

    /**
     * Instances of this class cannot be unserialized.
     *
     * @return void
     */
    public function __wakeup(): void
    {
        _doing_it_wrong(__FUNCTION__, esc_html__('Instances of this class cannot be unserialized.', 'site-alerts'), '1.0.0');
    }

    /**
     * Core constructor.
     *
     * Initializes the plugin by setting up WordPress hooks.
     */
    protected function __construct()
    {
        $this->setupHooks();
    }

    /**
     * Setup WordPress action hooks used by the plugin.
     *
     * @return void
     */
    private function setupHooks(): void
    {
        add_action('plugins_loaded', [$this, 'boot']);
    }

    /**
     * Boot the plugin services
     *
     * @return void
     */
    public function boot(): void
    {
        // Initialize logger
        Logger::init();

        // Register admin notices
        AdminNotices::register();

        // Register promo banner
        PromoBanner::register();

        // Boot core services (always loaded)
        $this->bootServices($this->getCoreServices());

        // Boot context-specific services
        if (is_admin()) {
            AdminUIAssets::getInstance()->register();
            ThemeManager::getInstance()->register();
            $this->bootServices($this->getAdminServices());
        } else {
            $this->bootServices($this->getFrontendServices());
        }

        /**
         * Action fired after all plugin services are booted.
         *
         * @param Core $core The Core instance.
         */
        do_action('site_alerts_loaded', $this);
    }

    /**
     * Initialize an array of service classes
     *
     * @param array $services Service class names.
     * @return void
     */
    private function bootServices(array $services): void
    {
        foreach ($services as $serviceClass) {
            if (!class_exists($serviceClass)) {
                continue;
            }

            // Handle singleton services
            if (method_exists($serviceClass, 'getInstance')) {
                $service = $serviceClass::getInstance();
            } else {
                $service = new $serviceClass();
            }

            if (method_exists($service, 'register')) {
                $service->register();
            }
        }
    }

    /**
     * Get core services.
     *
     * @return array
     */
    public function getCoreServices(): array
    {
        /**
         * Filter the core services.
         *
         * @param array $services Core service class names.
         */
        return apply_filters('site_alerts_core_services', $this->coreServices);
    }

    /**
     * Get admin services.
     *
     * @return array
     */
    public function getAdminServices(): array
    {
        /**
         * Filter the admin services.
         *
         * @param array $services Admin service class names.
         */
        return apply_filters('site_alerts_admin_services', $this->adminServices);
    }

    /**
     * Get frontend services.
     *
     * @return array
     */
    public function getFrontendServices(): array
    {
        /**
         * Filter the frontend services.
         *
         * @param array $services Frontend service class names.
         */
        return apply_filters('site_alerts_frontend_services', $this->frontendServices);
    }

    /**
     * Add a core service.
     *
     * @param string $serviceClass Service class name.
     * @return self
     */
    public function addCoreService(string $serviceClass): self
    {
        $this->coreServices[] = $serviceClass;
        return $this;
    }

    /**
     * Add an admin service.
     *
     * @param string $serviceClass Service class name.
     * @return self
     */
    public function addAdminService(string $serviceClass): self
    {
        $this->adminServices[] = $serviceClass;
        return $this;
    }

    /**
     * Add a frontend service.
     *
     * @param string $serviceClass Service class name.
     * @return self
     */
    public function addFrontendService(string $serviceClass): self
    {
        $this->frontendServices[] = $serviceClass;
        return $this;
    }
}
