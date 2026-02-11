<?php

namespace SiteAlerts\Admin;

use SiteAlerts\Components\AjaxComponent;
use SiteAlerts\Utils\DateTimeUtils;
use SiteAlerts\Utils\OptionUtils;
use SiteAlerts\Config\UserOptions;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class PromoBanner
 *
 * Handles the promo banner dismissal functionality.
 * Stores dismissal timestamp per user and checks if banner should be shown.
 *
 * @package SiteAlerts\Admin
 * @version 1.0.0
 */
class PromoBanner
{
    /**
     * Number of days the banner stays dismissed
     *
     * @var int
     */
    private const DISMISS_DURATION_DAYS = 14;

    /**
     * Whether the class has been initialized
     *
     * @var bool
     */
    private static bool $initialized = false;

    /**
     * Register hooks and AJAX handlers.
     *
     * @return void
     */
    public static function register(): void
    {
        if (self::$initialized) {
            return;
        }

        // Register AJAX handler for dismissing the promo banner
        AjaxComponent::register('dismiss_promo_banner', [self::class, 'handleDismiss'], false);

        self::$initialized = true;
    }

    /**
     * AJAX handler for dismissing the promo banner
     *
     * @return void
     */
    public static function handleDismiss(): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by AjaxComponent::register()

        // Check user capability
        if (!current_user_can('manage_options')) {
            AjaxComponent::sendError(__('Unauthorized.', 'site-alerts'), 403);
            return;
        }

        // Calculate dismissal end timestamp
        $dismissDays  = self::getDismissDuration();
        $dismissUntil = DateTimeUtils::timestamp() + ($dismissDays * DAY_IN_SECONDS);

        // Store per-user dismissal
        OptionUtils::setUserOption(UserOptions::PROMO_BANNER_DISMISSED_UNTIL, $dismissUntil);

        AjaxComponent::sendSuccess([
            'dismissed_until' => $dismissUntil,
            'days'            => $dismissDays,
        ], __('Banner dismissed successfully.', 'site-alerts'));
    }

    /**
     * Check if the promo banner should be shown for the current user
     *
     * @return bool
     */
    public static function shouldShowBanner(): bool
    {
        $dismissedUntil = OptionUtils::getUserOption(UserOptions::PROMO_BANNER_DISMISSED_UNTIL, 0);

        // Never dismissed
        if (empty($dismissedUntil)) {
            return true;
        }

        // Check if dismissal period has expired
        return DateTimeUtils::timestamp() > (int)$dismissedUntil;
    }

    /**
     * Get the dismiss duration in days
     * Filterable via 'site_alerts_promo_dismiss_duration' hook
     *
     * @return int
     */
    private static function getDismissDuration(): int
    {
        return (int)apply_filters(
            'site_alerts_promo_dismiss_duration',
            self::DISMISS_DURATION_DAYS
        );
    }
}
