<?php

namespace SiteAlerts\Utils;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class OptionUtils
 *
 * A helper class to manage plugin options and user meta in WordPress.
 *
 * @package SiteAlerts\Utils
 * @version 1.0.0
 */
class OptionUtils
{
    /**
     * Main option name in wp_options table
     *
     * @var string
     */
    protected static string $optionName = 'site_alerts';

    /**
     * Prefix for individual option keys
     *
     * @var string
     */
    protected static string $prefix = 'sa_';

    /**
     * Get full option key with prefix
     *
     * @param string $key
     * @return string
     */
    public static function getOptionName(string $key): string
    {
        return self::$prefix . $key;
    }

    /**
     * Get default plugin options
     *
     * @return array
     */
    public static function getDefaults(): array
    {
        return [
            //
        ];
    }

    /**
     * Get all plugin options (merged with defaults)
     *
     * @return array
     */
    public static function getAllOptions(): array
    {
        $options = get_option(self::$optionName, []);
        return array_merge(self::getDefaults(), is_array($options) ? $options : []);
    }

    /**
     * Get a single plugin option
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getOption(string $key, $default = null)
    {
        $options = self::getAllOptions();
        return $options[$key] ?? $default;
    }

    /**
     * Set/update a single plugin option
     *
     * @param string $key
     * @param mixed $value
     */
    public static function setOption(string $key, $value): void
    {
        $options       = get_option(self::$optionName, []);
        $options[$key] = $value;
        update_option(self::$optionName, $options);
    }

    /**
     * Delete a single plugin option
     *
     * @param string $key
     */
    public static function deleteOption(string $key): void
    {
        $options = get_option(self::$optionName, []);
        if (isset($options[$key])) {
            unset($options[$key]);
            update_option(self::$optionName, $options);
        }
    }

    /**
     * Reset all plugin options to defaults
     */
    public static function resetOptions(): void
    {
        update_option(self::$optionName, self::getDefaults());
    }

    /**
     * Get a single user-specific option
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getUserOption(string $key, $default = null)
    {
        $userId = get_current_user_id();
        if (!$userId) {
            return $default;
        }

        $options = get_user_meta($userId, self::$optionName, true) ?: [];
        return $options[$key] ?? $default;
    }

    /**
     * Set/update a single user-specific option
     *
     * @param string $key
     * @param mixed $value
     */
    public static function setUserOption(string $key, $value): void
    {
        $userId = get_current_user_id();
        if (!$userId) {
            return;
        }

        $options       = get_user_meta($userId, self::$optionName, true) ?: [];
        $options[$key] = $value;
        update_user_meta($userId, self::$optionName, $options);
    }

    /**
     * Delete a single user-specific option
     *
     * @param string $key
     */
    public static function deleteUserOption(string $key): void
    {
        $userId = get_current_user_id();
        if (!$userId) {
            return;
        }

        $options = get_user_meta($userId, self::$optionName, true) ?: [];
        if (isset($options[$key])) {
            unset($options[$key]);
            update_user_meta($userId, self::$optionName, $options);
        }
    }

    /**
     * Reset all user-specific options
     */
    public static function resetUserOptions(): void
    {
        $userId = get_current_user_id();
        if (!$userId) {
            return;
        }

        update_user_meta($userId, self::$optionName, []);
    }
}
