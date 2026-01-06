<?php

namespace SiteAlerts\Lifecycle;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class DeactivationHandler
 *
 * Handles plugin deactivation logic including cleanup of
 * scheduled events and transients.
 *
 * @package SiteAlerts\Lifecycle
 * @version 1.0.0
 */
class DeactivationHandler
{
    /**
     * Registered deactivation callbacks
     *
     * @var array
     */
    private static array $callbacks = [];

    /**
     * Register the deactivation hook.
     *
     * @return void
     */
    public static function register(): void
    {
        register_deactivation_hook(SA_PLUGIN_FILE, [self::class, 'deactivate']);
    }

    /**
     * Add a callback to run during deactivation.
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
     * Run deactivation logic.
     *
     * @param bool $networkWide Whether this is a network-wide deactivation.
     * @return void
     */
    public static function deactivate(bool $networkWide = false): void
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        if ($networkWide && is_multisite()) {
            self::networkDeactivate();
        } else {
            self::singleDeactivate();
        }

        /**
         * Fires after the plugin has been deactivated.
         *
         * @param bool $networkWide Whether this was a network-wide deactivation.
         */
        do_action('site_alerts_deactivated', $networkWide);
    }

    /**
     * Run deactivation for a single site.
     *
     * @return void
     */
    private static function singleDeactivate(): void
    {
        self::clearScheduledEvents();
        self::clearTransients();
        self::runCallbacks();
        self::flushRewriteRules();
    }

    /**
     * Run deactivation for all sites in a network.
     *
     * @return void
     */
    private static function networkDeactivate(): void
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Multisite network query requires direct access
        $blogIds = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");

        foreach ($blogIds as $blogId) {
            switch_to_blog($blogId);
            self::singleDeactivate();
            restore_current_blog();
        }
    }

    /**
     * Clear all scheduled cron events.
     *
     * @return void
     */
    public static function clearScheduledEvents(): void
    {
        // Default hooks to clear
        $defaultHooks = [
            'site_alerts_daily_cron',
        ];

        /**
         * Filter the list of cron hooks to clear on deactivation.
         *
         * @param array $hooks Array of hook names to clear.
         */
        $hooks = apply_filters('site_alerts_cron_hooks_to_clear', $defaultHooks);

        foreach ($hooks as $hook) {
            wp_clear_scheduled_hook($hook);
        }

        /**
         * Fires after scheduled events are cleared.
         */
        do_action('site_alerts_clear_scheduled_events');
    }

    /**
     * Clear plugin transients.
     *
     * @return void
     */
    public static function clearTransients(): void
    {
        global $wpdb;

        // Clear transients with our prefix
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bulk cleanup on deactivation requires direct query
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_site_alerts_%',
                '_transient_timeout_site_alerts_%'
            )
        );

        /**
         * Fires after transients are cleared.
         */
        do_action('site_alerts_clear_transients');
    }

    /**
     * Flush rewrite rules.
     *
     * @return void
     */
    public static function flushRewriteRules(): void
    {
        flush_rewrite_rules();
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
}
