<?php

namespace SiteAlerts\Lifecycle;

use SiteAlerts\Cache\CacheManager;
use SiteAlerts\Database\Schemas\CoreTables;
use SiteAlerts\Utils\CacheKeys;
use SiteAlerts\Utils\OptionUtils;
use SiteAlerts\Config\PluginMeta;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ActivationHandler
 *
 * Handles plugin activation logic including version tracking,
 * database table creation, and initial setup.
 *
 * @package SiteAlerts\Lifecycle
 * @version 1.0.0
 */
class ActivationHandler
{
    /**
     * Registered activation callbacks
     *
     * @var array
     */
    private static array $callbacks = [];

    /**
     * Registered table schema classes
     *
     * @var array
     */
    private static array $tableSchemas = [
        CoreTables::class,
    ];

    /**
     * Register the activation hook.
     *
     * @return void
     */
    public static function register(): void
    {
        register_activation_hook(SA_PLUGIN_FILE, [self::class, 'activate']);
    }

    /**
     * Add a callback to run during activation.
     *
     * @param callable $callback The callback function.
     * @param int $priority Priority (lower = earlier).
     * @return void
     */
    public static function addCallback(callable $callback, int $priority = 10): void
    {
        self::$callbacks[$priority][] = $callback;
    }

    /**
     * Add a table schema class to be registered during activation.
     *
     * @param string $schemaClass The fully qualified class name.
     * @return void
     */
    public static function addTableSchema(string $schemaClass): void
    {
        self::$tableSchemas[] = $schemaClass;
    }

    /**
     * Run activation logic.
     *
     * @param bool $networkWide Whether this is a network-wide activation.
     * @return void
     */
    public static function activate(bool $networkWide = false): void
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        if ($networkWide && is_multisite()) {
            self::networkActivate();
        } else {
            self::singleActivate();
        }

        /**
         * Fires after the plugin has been activated.
         *
         * @param bool $networkWide Whether this was a network-wide activation.
         */
        do_action('site_alerts_activated', $networkWide);
    }

    /**
     * Run activation for a single site.
     *
     * @return void
     */
    private static function singleActivate(): void
    {
        self::runUpgrades();
        self::setDefaultOptions();
        self::createTables();
        self::scheduleEvents();
        self::runCallbacks();
        self::flushRewriteRules();
        self::setVersion();
    }

    /**
     * Run activation for all sites in a network.
     *
     * @return void
     */
    private static function networkActivate(): void
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Multisite network query requires direct access
        $blogIds = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");

        foreach ($blogIds as $blogId) {
            switch_to_blog($blogId);
            self::singleActivate();
            restore_current_blog();
        }
    }

    /**
     * Run version-based upgrades.
     *
     * @return void
     */
    public static function runUpgrades(): void
    {
        $currentVersion = OptionUtils::getMeta(PluginMeta::VERSION, '0.0.0');
        $newVersion     = SA_VERSION;

        if (version_compare($currentVersion, $newVersion, '<')) {
            /**
             * Fires when the plugin is upgraded.
             *
             * @param string $currentVersion The version being upgraded from.
             * @param string $newVersion The version being upgraded to.
             */
            do_action('site_alerts_upgrade', $currentVersion, $newVersion);

            // Run version-specific upgrades
            self::runVersionUpgrades($currentVersion, $newVersion);
        }
    }

    /**
     * Run version-specific upgrade routines.
     *
     * @param string $fromVersion Version upgrading from.
     * @param string $toVersion Version upgrading to.
     * @return void
     */
    private static function runVersionUpgrades(string $fromVersion, string $toVersion): void
    {
        /**
         * Filter to add custom version upgrades.
         *
         * @param string $fromVersion Version upgrading from.
         * @param string $toVersion Version upgrading to.
         */
        apply_filters('site_alerts_version_upgrades', $fromVersion, $toVersion);
    }

    /**
     * Set the plugin version in the database.
     *
     * @return void
     */
    public static function setVersion(): void
    {
        OptionUtils::setMeta(PluginMeta::VERSION, SA_VERSION);
    }

    /**
     * Create database tables.
     *
     * @return void
     */
    public static function createTables(): void
    {
        /**
         * Filter the table schema classes to register.
         *
         * @param array $tableSchemas Array of table schema class names.
         */
        $schemas = apply_filters('site_alerts_table_schemas', self::$tableSchemas);

        // Register all schema hooks before firing the action
        foreach ($schemas as $schemaClass) {
            if (class_exists($schemaClass) && method_exists($schemaClass, 'register')) {
                $schemaClass::register();
            }
        }

        /**
         * Fires when database tables should be created.
         * Hook into this to create your custom tables.
         */
        do_action('site_alerts_create_tables');
    }

    /**
     * Set default plugin options.
     *
     * @return void
     */
    public static function setDefaultOptions(): void
    {
        $defaults = OptionUtils::getDefaults();

        foreach ($defaults as $key => $value) {
            if (OptionUtils::getOption($key) === null) {
                OptionUtils::setOption($key, $value);
            }
        }

        /**
         * Fires after default options are set.
         */
        do_action('site_alerts_set_default_options');
    }

    /**
     * Schedule cron events.
     *
     * @return void
     */
    public static function scheduleEvents(): void
    {
        /**
         * Fires when cron events should be scheduled.
         * Hook into this to schedule your custom cron jobs.
         */
        do_action('site_alerts_schedule_events');
    }

    /**
     * Flush rewrite rules.
     *
     * @return void
     */
    public static function flushRewriteRules(): void
    {
        // Set a cache flag to flush rules on next init
        CacheManager::getInstance()->set(CacheKeys::flushRewriteRules(), true, 60);
    }

    /**
     * Run registered callbacks.
     *
     * @return void
     */
    private static function runCallbacks(): void
    {
        ksort(self::$callbacks);

        foreach (self::$callbacks as $callbacks) {
            foreach ($callbacks as $callback) {
                $callback();
            }
        }
    }

    /**
     * Check and flush rewrite rules if needed.
     * Should be called on 'init' hook.
     *
     * @return void
     */
    public static function maybeFlushRewriteRules(): void
    {
        $cache = CacheManager::getInstance();

        if ($cache->get(CacheKeys::flushRewriteRules())) {
            $cache->delete(CacheKeys::flushRewriteRules());
            flush_rewrite_rules();
        }
    }
}
