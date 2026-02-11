<?php

namespace SiteAlerts\Config;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @package SiteAlerts\Config
 * @version 1.0.0
 */
class PluginMeta
{
    /**
     *
     * @var string
     */
    public const VERSION = 'version';

    /**
     * @var string
     */
    public const DB_VERSION = 'db_version';

    /**
     * @var string
     */
    public const LAST_DAILY_RUN = 'last_daily_run';
}