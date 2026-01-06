<?php

namespace SiteAlerts\Services\Admin\Alerts;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AlertsManager
 *
 * Manages the main alerts admin page and menu registration.
 *
 * @package SiteAlerts\Services\Admin\Alerts
 * @version 1.0.0
 */
class AlertsManager
{
    /**
     * Register hooks and filters.
     *
     * @return void
     */
    public function register(): void
    {
        add_filter('site_alerts_menu_items', [$this, 'addMenuItem']);
    }

    /**
     * Add alerts menu items.
     *
     * @param array $items Existing menu items.
     * @return array Modified menu items.
     */
    public function addMenuItem(array $items): array
    {
        /**
         * Apply a filter to determine the position of the plugin menu item.
         *
         * @param float $position The default position of the menu item in the admin menu.
         *
         * @return float The possibly modified position after applying filters.
         */
        $position = apply_filters('site_alerts_plugins_menu_item_position', 65.0);

        $items[] = [
            'id'       => 'site-alerts',
            'title'    => esc_html__('Site Alerts', 'site-alerts'),
            'icon'     => 'dashicons-warning',
            'position' => $position,
            'callback' => AlertsPage::class,
        ];

        $items[] = [
            'id'       => 'site-alerts',
            'title'    => esc_html__('Alerts', 'site-alerts'),
            'parentId' => 'site-alerts',
            'callback' => AlertsPage::class,
        ];

        return $items;
    }
}