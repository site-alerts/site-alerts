<?php

namespace SiteAlerts\Components;

use SiteAlerts\Config\PrefixConfig;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AssetsComponent
 *
 * Handles registration, enqueueing, and localization of scripts and styles.
 *
 * @package SiteAlerts\Components
 * @version 1.0.0
 */
class AssetsComponent
{
    /**
     * Assets URL
     *
     * @var string
     */
    public static string $assetsUrl = SA_ASSETS;

    /**
     * Enqueue a JavaScript file.
     *
     * @param string $handle
     * @param ?string $src
     * @param array $deps
     * @param string|null $version
     * @param bool $inFooter
     */
    public static function enqueueScript(string $handle, ?string $src = null, array $deps = [], ?string $version = null, bool $inFooter = false): void
    {
        $handle  = self::getHandle($handle);
        $version = $version ?? SA_VERSION;

        wp_enqueue_script($handle, self::getSrc($src), $deps, $version, $inFooter);
    }

    /**
     * Register a JavaScript file.
     *
     * @param string $handle
     * @param string $src
     * @param array $deps
     * @param string|null $version
     * @param bool $inFooter
     */
    public static function registerScript(string $handle, string $src, array $deps = [], ?string $version = null, bool $inFooter = false): void
    {
        $handle  = self::getHandle($handle);
        $version = $version ?? SA_VERSION;

        wp_register_script($handle, self::getSrc($src), $deps, $version, $inFooter);
    }

    /**
     * Enqueue a CSS file.
     *
     * @param string $handle
     * @param ?string $src
     * @param array $deps
     * @param string $media
     * @param string|null $version
     */
    public static function enqueueStyle(string $handle, ?string $src = null, array $deps = [], string $media = 'all', ?string $version = null): void
    {
        $handle  = self::getHandle($handle);
        $version = $version ?? SA_VERSION;

        wp_enqueue_style($handle, self::getSrc($src), $deps, $version, $media);
    }

    /**
     * Register a CSS file.
     *
     * @param string $handle
     * @param string $src
     * @param array $deps
     * @param string $media
     * @param string|null $version
     */
    public static function registerStyle(string $handle, string $src, array $deps = [], string $media = 'all', ?string $version = null): void
    {
        $handle  = self::getHandle($handle);
        $version = $version ?? SA_VERSION;

        wp_register_style($handle, self::getSrc($src), $deps, $version, $media);
    }

    /**
     * Localize a script with data.
     *
     * @param string $handle
     * @param string $objectName
     * @param array $data
     */
    public static function localizeScript(string $handle, string $objectName, array $data): void
    {
        $handle = self::getHandle($handle);
        wp_localize_script($handle, $objectName, $data);
    }

    /**
     * Get the full URL for an asset.
     *
     * @param ?string $src
     * @return string
     */
    public static function getSrc(?string $src): string
    {
        return $src ? rtrim(self::$assetsUrl, '/') . '/' . ltrim($src, '/') : '';
    }

    /**
     * Generate a standardized handle name.
     *
     * @param string $handle
     * @return string
     */
    public static function getHandle(string $handle): string
    {
        return PrefixConfig::handle(sanitize_key($handle));
    }
}
