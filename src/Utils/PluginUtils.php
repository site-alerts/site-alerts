<?php

namespace SiteAlerts\Utils;

use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class PluginUtils
 *
 * A utility class for managing WordPress plugins programmatically.
 *
 * @package SiteAlerts\Utils
 * @version 1.0.0
 */
class PluginUtils
{

    /**
     * Get all installed plugins.
     *
     * @return array An associative array of all installed plugins.
     */
    public static function getAllPlugins(): array
    {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return get_plugins();
    }

    /**
     * Check if a plugin exists by its file path.
     *
     * @param string $pluginFile Plugin file path (e.g., "plugin-folder/plugin-file.php").
     * @return bool True if the plugin exists, false otherwise.
     */
    public static function pluginExists(string $pluginFile): bool
    {
        $allPlugins = self::getAllPlugins();
        return isset($allPlugins[$pluginFile]);
    }

    /**
     * Check if a plugin is active.
     *
     * @param string $pluginFile Plugin file path.
     * @return bool True if the plugin is active, false otherwise.
     */
    public static function isPluginActive(string $pluginFile): bool
    {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return is_plugin_active($pluginFile);
    }

    /**
     * Activate a plugin.
     *
     * @param string $pluginFile Plugin file path.
     * @return bool|WP_Error True on success, WP_Error on failure, false if plugin doesn't exist.
     */
    public static function activatePlugin($pluginFile)
    {
        if (!self::pluginExists($pluginFile)) {
            return false;
        }

        if (!function_exists('activate_plugin')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $result = activate_plugin($pluginFile);
        return is_wp_error($result) ? $result : true;
    }

    /**
     * Deactivate a plugin.
     *
     * @param string $pluginFile Plugin file path.
     * @return bool True if deactivated successfully, false if plugin doesn't exist.
     */
    public static function deactivatePlugin(string $pluginFile): bool
    {
        if (!self::pluginExists($pluginFile)) {
            return false;
        }

        if (!function_exists('deactivate_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        deactivate_plugins($pluginFile);
        return !self::isPluginActive($pluginFile);
    }

    /**
     * Get information about a plugin.
     *
     * @param string $pluginFile Plugin file path.
     * @return array|null Plugin information array or null if plugin does not exist.
     */
    public static function getPluginInfo(string $pluginFile): ?array
    {
        $allPlugins = self::getAllPlugins();
        return $allPlugins[$pluginFile] ?? null;
    }

    /**
     * Check plugin dependencies.
     *
     * @param array $requiredPlugins List of required plugin file paths.
     * @return array Returns an array of missing or inactive plugins. Empty array if all dependencies are met.
     */
    public static function checkDependencies(array $requiredPlugins): array
    {
        $missing = [];

        foreach ($requiredPlugins as $pluginFile) {
            if (!self::pluginExists($pluginFile) || !self::isPluginActive($pluginFile)) {
                $missing[] = $pluginFile;
            }
        }

        return $missing;
    }
}
