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
    protected const OPTION_NAME = 'site_alerts';

    /**
     * Prefix for individual option keys
     *
     * @var string
     */
    protected const META_PREFIX = 'site_alerts_';

    /**
     * Get full option key with prefix
     *
     * @param string $key
     * @return string
     */
    public static function getMetaOptionName(string $key): string
    {
        return self::META_PREFIX . $key;
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
        $options = get_option(self::OPTION_NAME, self::getDefaults());
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
        $options       = get_option(self::OPTION_NAME, self::getDefaults());
        $options[$key] = $value;
        update_option(self::OPTION_NAME, $options);
    }

    /**
     * Delete a single plugin option
     *
     * @param string $key
     */
    public static function deleteOption(string $key): void
    {
        $options = get_option(self::OPTION_NAME, []);
        if (isset($options[$key])) {
            unset($options[$key]);
            update_option(self::OPTION_NAME, $options);
        }
    }

    /**
     * Reset all plugin options to defaults
     */
    public static function resetOptions(): void
    {
        update_option(self::OPTION_NAME, self::getDefaults());
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

        $options = get_user_meta($userId, self::OPTION_NAME, true) ?: [];
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

        $options       = get_user_meta($userId, self::OPTION_NAME, true) ?: [];
        $options[$key] = $value;
        update_user_meta($userId, self::OPTION_NAME, $options);
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

        $options = get_user_meta($userId, self::OPTION_NAME, true) ?: [];
        if (isset($options[$key])) {
            unset($options[$key]);
            update_user_meta($userId, self::OPTION_NAME, $options);
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

        update_user_meta($userId, self::OPTION_NAME, []);
    }

    /**
     * Get plugin meta option (standalone option)
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getMeta(string $key, $default = null)
    {
        return get_option(self::getMetaOptionName($key), $default);
    }

    /**
     * Set plugin meta option (standalone option)
     *
     * @param string $key
     * @param mixed $value
     * @param bool|null $autoload
     */
    public static function setMeta(string $key, $value, ?bool $autoload = null): void
    {
        update_option(self::getMetaOptionName($key), $value, $autoload);
    }

    /**
     * Delete plugin meta option
     *
     * @param string $key
     */
    public static function deleteMeta(string $key): void
    {
        delete_option(self::getMetaOptionName($key));
    }
}
