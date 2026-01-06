<?php

namespace SiteAlerts\Services\Admin\Alerts;

use SiteAlerts\Abstracts\AbstractAdminPage;
use SiteAlerts\Admin\PromoBanner;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AlertsPage
 *
 * Admin page for displaying site alerts dashboard.
 *
 * @package SiteAlerts\Services\Admin\Alerts
 * @version 1.0.0
 */
class AlertsPage extends AbstractAdminPage
{
    /**
     * Returns the template path for the alerts page.
     *
     * @return string
     */
    protected function getTemplate(): string
    {
        return 'admin/pages/alerts';
    }

    /**
     * Returns the context array for the alerts page body.
     *
     * Uses AlertsPageContext to build state-aware section data.
     *
     * @return array
     */
    protected function getBodyContext(): array
    {
        $context = new AlertsPageContext();

        // Check if promo banner should be shown
        $showPromoBanner = PromoBanner::shouldShowBanner();

        return [
            'pageTitle'       => __('Site Alerts', 'site-alerts'),
            'pageSubtitle'    => __('Unusual activity on your site — with recommended actions.', 'site-alerts'),
            'statusLine'      => $context->getStatusLine(),
            'statusSummary'   => $context->getStatusSummary(),
            'digestCards'     => $context->getDigestCards(),
            'latestAlerts'    => $context->getLatestAlerts(),
            'history'         => $context->getHistory(),
            'showPromoBanner' => $showPromoBanner,
        ];
    }
}