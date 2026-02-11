<?php

namespace SiteAlerts\Config;

if (!defined('ABSPATH')) {
    exit;
}


/**
 * Class UserOptions
 *
 * Centralizes all user-specific option keys (user_meta keys).
 *
 * @package SiteAlerts\Config
 * @version 1.0.0
 */
final class UserOptions
{
    /**
     * Stores the ID of the most recent alert the user has seen.
     * Used to determine which alerts are "new" for the menu badge count.
     *
     * @var string
     */
    public const LAST_SEEN_ALERT_ID = 'last_seen_alert_id';

    /**
     * @var string
     */
    public const ADMIN_THEME = 'ADMIN_THEME';

    /**
     * @var string
     */
    public const PROMO_BANNER_DISMISSED_UNTIL = 'promo_banner_dismissed_until';

    /**
     * @var string
     */
    public const DISMISSED_NOTICES = 'dismissed_notices';
}