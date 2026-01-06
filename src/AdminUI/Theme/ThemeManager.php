<?php

namespace SiteAlerts\AdminUI\Theme;

use SiteAlerts\Abstracts\AbstractSingleton;
use SiteAlerts\Components\AjaxComponent;
use SiteAlerts\Utils\OptionUtils;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ThemeManager
 *
 * Manages the admin UI theme (light / dark) on a per-user basis.
 * Handles theme persistence, AJAX-based switching, and UI helpers.
 *
 * @package SiteAlerts\AdminUI\Theme
 */
class ThemeManager extends AbstractSingleton
{
    /**
     * Light admin theme identifier.
     */
    public const THEME_LIGHT = 'light';

    /**
     * Dark admin theme identifier.
     */
    public const THEME_DARK = 'dark';

    /**
     * User option key for storing the admin theme (without prefix).
     */
    private const ADMIN_THEME_KEY = 'admin_theme';

    /**
     * Whether hooks and AJAX handlers have already been registered.
     *
     * Prevents duplicate registration.
     */
    private bool $registered = false;

    /**
     * Register hooks and AJAX handlers for admin theme management.
     *
     * This method is idempotent and safe to call multiple times.
     *
     * @return void
     */
    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        $this->registered = true;

        // Register AJAX handler for theme switching.
        AjaxComponent::register('switch_theme', [$this, 'handleThemeSwitch'], false, true);

        // Add theme-specific class to the admin body.
        add_action('admin_body_class', [$this, 'addThemeBodyClass']);
    }

    /**
     * Get the currently active admin theme for the current user.
     *
     * Falls back to the light theme if the stored value is invalid
     * or missing.
     *
     * @return string One of THEME_LIGHT or THEME_DARK.
     */
    public function getCurrentTheme(): string
    {
        $theme = OptionUtils::getUserOption(self::ADMIN_THEME_KEY, self::THEME_LIGHT);

        return in_array($theme, [self::THEME_LIGHT, self::THEME_DARK], true)
            ? $theme
            : self::THEME_LIGHT;
    }

    /**
     * Set the admin theme for the current user.
     *
     * @param string $theme Theme identifier.
     *
     * @return bool True on success, false if the theme is invalid.
     */
    public function setTheme(string $theme): bool
    {
        if (!in_array($theme, [self::THEME_LIGHT, self::THEME_DARK], true)) {
            return false;
        }

        OptionUtils::setUserOption(self::ADMIN_THEME_KEY, $theme);
        return true;
    }

    /**
     * Check whether the current admin theme is dark mode.
     *
     * @return bool
     */
    public function isDarkMode(): bool
    {
        return $this->getCurrentTheme() === self::THEME_DARK;
    }

    /**
     * Check whether the current admin theme is light mode.
     *
     * @return bool
     */
    public function isLightMode(): bool
    {
        return $this->getCurrentTheme() === self::THEME_LIGHT;
    }

    /**
     * Handle AJAX requests for switching the admin theme.
     *
     * Expects a `theme` value in the POST payload.
     * Nonce verification is handled by AjaxComponent.
     *
     * @return void
     */
    public function handleThemeSwitch(): void
    {
        $theme = '';
        if (isset($_POST['theme'])) {
            $theme = sanitize_text_field(wp_unslash($_POST['theme']));
        }

        if ($this->setTheme($theme)) {
            AjaxComponent::sendSuccess(
                ['theme' => $theme],
                __('Theme switched successfully.', 'site-alerts')
            );
        } else {
            AjaxComponent::sendError(
                __('Invalid theme.', 'site-alerts'),
                400
            );
        }
    }

    /**
     * Append the current theme class to the admin body element.
     *
     * @param string $classes Existing admin body classes.
     *
     * @return string Modified body classes.
     */
    public function addThemeBodyClass(string $classes): string
    {
        return $classes . ' sa-theme-' . $this->getCurrentTheme();
    }

    /**
     * Get the HTML data attribute representing the current admin theme.
     *
     * @return string HTML-safe data attribute string.
     */
    public function getThemeAttribute(): string
    {
        return 'data-sa-theme="' . esc_attr($this->getCurrentTheme()) . '"';
    }

    /**
     * Static shortcut for retrieving the current admin theme.
     *
     * @return string
     */
    public static function theme(): string
    {
        return self::getInstance()->getCurrentTheme();
    }

    /**
     * Static shortcut for checking dark mode state.
     *
     * @return bool
     */
    public static function isDark(): bool
    {
        return self::getInstance()->isDarkMode();
    }
}