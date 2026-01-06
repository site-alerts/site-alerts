<?php
/**
 * ============================================
 * AUTO-GENERATED FILE - DO NOT EDIT DIRECTLY
 * ============================================
 * Generated from: config/prefix.config.cjs
 * Regenerate with: npm run build
 * ============================================
 */

namespace SiteAlerts\Config;

/**
 * Prefix Configuration Class
 *
 * Provides centralized access to all prefix values.
 * Used by PHP components for consistent naming.
 *
 * @package SiteAlerts\Config
 * @version 1.0.0
 */
final class PrefixConfig
{
    /**
     * Core prefix (e.g., 'sa')
     */
    public const PREFIX = 'sa';

    /**
     * Uppercase constant prefix (e.g., 'SA')
     */
    public const CONSTANT_PREFIX = 'SA';

    /**
     * JavaScript namespace (e.g., 'SA')
     */
    public const JS_NAMESPACE = 'SA';

    /**
     * CSS class prefix with hyphen (e.g., 'sa-')
     */
    public const CSS_PREFIX = 'sa-';

    /**
     * Data attribute prefix (e.g., 'data-sa')
     */
    public const DATA_ATTR = 'data-sa';

    /**
     * CSS custom property prefix (e.g., '--sa')
     */
    public const CSS_VAR = '--sa';

    /**
     * PHP function/option prefix with underscore (e.g., 'sa_')
     */
    public const PHP_PREFIX = 'sa_';

    /**
     * WordPress handle prefix (e.g., 'sa-')
     */
    public const HANDLE_PREFIX = 'sa-';

    /**
     * Event prefix for JavaScript events (e.g., 'sa')
     */
    public const EVENT_PREFIX = 'sa';

    /**
     * Localized config object name (e.g., 'saConfig')
     */
    public const CONFIG_OBJECT = 'saConfig';

    /**
     * Storage prefix for localStorage (e.g., 'sa')
     */
    public const STORAGE_PREFIX = 'sa';

    /**
     * Prevent instantiation
     */
    private function __construct()
    {
    }

    /**
     * Get prefixed CSS class name
     *
     * @param string $name Class name without prefix
     * @return string Prefixed class name (e.g., 'sa-btn')
     */
    public static function cssClass(string $name): string
    {
        return self::CSS_PREFIX . $name;
    }

    /**
     * Get prefixed data attribute name
     *
     * @param string $name Attribute name without prefix
     * @return string Prefixed attribute (e.g., 'data-sa-toggle')
     */
    public static function dataAttr(string $name): string
    {
        return self::DATA_ATTR . '-' . $name;
    }

    /**
     * Get prefixed CSS variable name
     *
     * @param string $name Variable name without prefix
     * @return string Prefixed variable (e.g., '--sa-primary')
     */
    public static function cssVar(string $name): string
    {
        return self::CSS_VAR . '-' . $name;
    }

    /**
     * Get prefixed option/meta key
     *
     * @param string $name Key name without prefix
     * @return string Prefixed key (e.g., 'sa_settings')
     */
    public static function optionKey(string $name): string
    {
        return self::PHP_PREFIX . $name;
    }

    /**
     * Get prefixed WordPress handle
     *
     * @param string $name Handle name without prefix
     * @return string Prefixed handle
     */
    public static function handle(string $name): string
    {
        return self::HANDLE_PREFIX . $name;
    }

    /**
     * Get prefixed AJAX action name
     *
     * @param string $name Action name without prefix
     * @return string Prefixed action (e.g., 'sa_save_settings')
     */
    public static function ajaxAction(string $name): string
    {
        return self::PHP_PREFIX . $name;
    }

    /**
     * Get prefixed nonce name
     *
     * @param string $name Nonce name without prefix
     * @return string Prefixed nonce (e.g., 'sa_nonce')
     */
    public static function nonce(string $name = 'nonce'): string
    {
        return self::PHP_PREFIX . $name;
    }
}
