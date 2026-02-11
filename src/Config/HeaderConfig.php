<?php

namespace SiteAlerts\Config;

use SiteAlerts\Utils\MenuUtils;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class HeaderConfig
 *
 * Centralized configuration for admin header defaults.
 *
 * Provides default header elements (brand, navigation, actions) that can be
 * overridden, extended, or disabled on a per-page basis.
 *
 * Usage in admin pages:
 * - Override getHeaderContext() to customize the header
 * - Use HeaderConfig::getDefaults() as a base
 * - Merge, extend, or replace specific sections
 *
 * @package SiteAlerts\Config
 * @version 1.0.0
 */
final class HeaderConfig
{
    /**
     * Get default header configuration.
     *
     * Returns complete default header context that can be used as-is
     * or merged with page-specific overrides.
     *
     * @return array Default header context
     */
    public static function getDefaults(): array
    {
        return [
            'title'     => self::getDefaultTitle(),
            'titleLink' => self::getDefaultTitleLink(),
            'version'   => self::getDefaultVersion(),
            'logoUrl'   => self::getDefaultLogoUrl(),
            'navItems'  => self::getDefaultNavItems(),
            'actions'   => self::getDefaultActions(),
        ];
    }

    /**
     * Get default plugin title.
     *
     * @return string
     */
    public static function getDefaultTitle(): string
    {
        return esc_html__('Site Alerts', 'site-alerts');
    }

    /**
     * Get default title link (main plugin page).
     *
     * @return string
     */
    public static function getDefaultTitleLink(): string
    {
        return MenuUtils::getUrl(SA_SLUG);
    }

    /**
     * Get default version string.
     *
     * @return string
     */
    public static function getDefaultVersion(): string
    {
        return defined('SA_VERSION') ? SA_VERSION : '1.0.0';
    }

    /**
     * Get default logo URL.
     *
     * @return string Empty by default, override to add logo
     */
    public static function getDefaultLogoUrl(): string
    {
        return '';
    }

    /**
     * Get default navigation items.
     *
     * Returns the main navigation menu with automatic active state detection.
     *
     * @return array Navigation items
     */
    public static function getDefaultNavItems(): array
    {
        return [];
    }

    /**
     * Get default action buttons.
     *
     * @return array Action buttons
     */
    public static function getDefaultActions(): array
    {
        return [];
    }

    /**
     * Merge page-specific context with defaults.
     *
     * Performs a smart merge that:
     * - Replaces scalar values (title, version, etc.)
     * - Merges arrays (navItems, actions) by key
     * - Allows removal of items by setting value to null/false
     *
     * @param array $overrides Page-specific overrides
     * @param array|null $defaults Base defaults (optional, uses getDefaults() if not provided)
     * @return array Merged context
     */
    public static function merge(array $overrides, ?array $defaults = null): array
    {
        $defaults = $defaults ?? self::getDefaults();
        $result   = $defaults;

        foreach ($overrides as $key => $value) {
            // Allow complete removal by setting to null or false
            if ($value === null || $value === false) {
                unset($result[$key]);
                continue;
            }

            // For arrays (nav_items, actions), do a smart merge
            if (is_array($value) && isset($result[$key]) && is_array($result[$key])) {
                $result[$key] = self::mergeArrayItems($result[$key], $value);
            } else {
                // For scalars, just replace
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Merge array items (for navItems, actions).
     *
     * - Items with matching keys are replaced
     * - New items are added
     * - Items set to null/false are removed
     *
     * @param array $defaults Default items
     * @param array $overrides Override items
     * @return array Merged items (re-indexed for template)
     */
    private static function mergeArrayItems(array $defaults, array $overrides): array
    {
        $result = $defaults;

        foreach ($overrides as $key => $item) {
            // Remove item if set to null/false
            if ($item === null || $item === false) {
                unset($result[$key]);
                continue;
            }

            // Add or replace item
            $result[$key] = $item;
        }

        // Re-index array to ensure proper iteration in templates
        return array_values($result);
    }

    /**
     * Get defaults with specific items disabled.
     *
     * Convenience method for pages that want defaults but with
     * certain navigation items or actions removed.
     *
     * @param array $disableNavItems Nav item keys to disable
     * @param array $disableActions Action keys to disable
     * @return array Modified defaults
     */
    public static function getDefaultsWithout(array $disableNavItems = [], array $disableActions = []): array
    {
        $defaults = self::getDefaults();

        // Remove specified nav items
        foreach ($disableNavItems as $key) {
            unset($defaults['navItems'][$key]);
        }

        // Remove specified actions
        foreach ($disableActions as $key) {
            unset($defaults['actions'][$key]);
        }

        // Re-index arrays
        $defaults['navItems'] = array_values($defaults['navItems']);
        $defaults['actions']  = array_values($defaults['actions']);

        return $defaults;
    }

    /**
     * Get minimal header (brand only, no nav or actions).
     *
     * Useful for simple pages or wizards.
     *
     * @return array Minimal header context
     */
    public static function getMinimal(): array
    {
        return [
            'title'     => self::getDefaultTitle(),
            'titleLink' => self::getDefaultTitleLink(),
            'version'   => self::getDefaultVersion(),
            'logoUrl'   => self::getDefaultLogoUrl(),
            'navItems'  => [],
            'actions'   => [],
        ];
    }

    /**
     * Add a navigation item to defaults.
     *
     * @param string $key Unique key for the item
     * @param array $item Navigation item configuration
     * @param string|null $after Optional key to insert after (null = end)
     * @return array Modified nav items
     */
    public static function addNavItem(string $key, array $item, ?string $after = null): array
    {
        $navItems = self::getDefaultNavItems();

        if ($after === null || !isset($navItems[$after])) {
            $navItems[$key] = $item;
        } else {
            $result = [];
            foreach ($navItems as $k => $v) {
                $result[$k] = $v;
                if ($k === $after) {
                    $result[$key] = $item;
                }
            }
            $navItems = $result;
        }

        return $navItems;
    }

    /**
     * Add an action button to defaults.
     *
     * @param string $key Unique key for the action
     * @param array $action Action configuration
     * @param bool $prepend Add at beginning instead of end
     * @return array Modified actions
     */
    public static function addAction(string $key, array $action, bool $prepend = false): array
    {
        $actions = self::getDefaultActions();

        if ($prepend) {
            $actions = [$key => $action] + $actions;
        } else {
            $actions[$key] = $action;
        }

        return $actions;
    }
}
