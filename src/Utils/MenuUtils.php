<?php
/**
 * Menu Utilities
 *
 * Helper functions for menu-related operations like slug prefixing and URL generation.
 *
 * @package SiteAlerts\Utils
 */

namespace SiteAlerts\Utils;

use SiteAlerts\Config\PrefixConfig;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MenuUtils
 *
 * Utility class for menu-related operations.
 */
class MenuUtils
{
    /**
     * Get the prefixed menu slug.
     *
     * @param string $id Menu ID (with or without prefix)
     * @return string Prefixed menu slug (e.g., 'sa_site-alerts')
     */
    public static function getSlug(string $id): string
    {
        $prefix = PrefixConfig::PHP_PREFIX;
        return strpos($id, $prefix) === 0 ? $id : $prefix . $id;
    }

    /**
     * Get the admin URL for a menu page.
     *
     * @param string $id Menu ID (with or without prefix)
     * @return string Full admin URL
     */
    public static function getUrl(string $id): string
    {
        return admin_url('admin.php?page=' . self::getSlug($id));
    }
}
