<?php

namespace SiteAlerts\Services\Frontend\Traffic;

use SiteAlerts\Abstracts\AbstractSingleton;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TrafficManager
 *
 * Manages frontend traffic tracking services.
 *
 * @package SiteAlerts\Services\Frontend\Traffic
 * @version 1.0.0
 */
class TrafficManager extends AbstractSingleton
{
    /**
     * Register all traffic tracking services.
     *
     * @return void
     */
    public function register(): void
    {
        TrafficCollector::getInstance()->register();
        NotFoundTracker::getInstance()->register();
    }
}
