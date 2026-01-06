<?php

namespace SiteAlerts\Abstracts;

use SiteAlerts\Config\HeaderConfig;
use SiteAlerts\Utils\TemplateUtils;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AbstractAdminPage
 *
 * This abstract class provides a base structure for admin pages.
 * It handles rendering the page with a header, body, and footer
 * using templates.
 *
 * Header Configuration:
 * - Default header elements are provided by HeaderConfig
 * - Override getHeaderContext() to customize per page
 * - Use HeaderConfig::merge() to extend defaults
 * - Set nav_items/actions to null to disable them
 *
 * @package SiteAlerts\Abstracts
 * @version 1.0.0
 */
abstract class AbstractAdminPage extends AbstractSingleton
{
    /**
     * Renders the admin page.
     *
     * The page is rendered in three parts:
     * 1. Header
     * 2. Body (from the template returned by getTemplate())
     * 3. Footer
     *
     * @return void
     */
    public function render(): void
    {
        $sections = [
            'header' => TemplateUtils::renderTemplate(
                'admin/layouts/header',
                $this->getHeaderContext()
            ),
            'body'   => TemplateUtils::renderTemplate(
                $this->getTemplate(),
                $this->getBodyContext()
            ),
            'footer' => TemplateUtils::renderTemplate(
                'admin/layouts/footer',
                $this->getFooterContext()
            ),
        ];

        $output = '';

        foreach ($sections as $key => $section) {
            if ($section === false) {
                /**
                 * Fires when one of the admin page sections fails to render.
                 *
                 * @param string $key Section key: 'header', 'body', or 'footer'.
                 * @param object $screen Current screen object (this class instance).
                 */
                do_action('site_alerts_admin_render_section_missing', $key, $this);
                continue;
            }

            $output .= $section;
        }

        /**
         * Filter the full admin page output before printing.
         *
         * @param string $output Full HTML to be printed.
         * @param object $screen Current class instance.
         */
        $output = apply_filters('site_alerts_admin_render_output', $output, $this);

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $output;
    }

    /**
     * Returns the template path for the body of the page.
     *
     * This method must be implemented by subclasses to provide
     * the specific template for the admin page.
     *
     * @return string
     */
    abstract protected function getTemplate(): string;

    /**
     * Returns the context array used for rendering the header template.
     *
     * By default, returns HeaderConfig::getDefaults() which provides:
     * - Plugin title and version
     * - Default navigation (Dashboard, Settings, Tools)
     * - Default actions (Documentation link)
     *
     * Override this method to customize the header for your page.
     *
     * Examples:
     *
     * 1. Use defaults as-is:
     *    return parent::getHeaderContext();
     *
     * 2. Extend defaults with additional nav item:
     *    return HeaderConfig::merge([
     *        'navItems' => [
     *            'reports' => [
     *                'label' => 'Reports',
     *                'url'   => admin_url('admin.php?page=my-reports'),
     *                'icon'  => 'dashicons-chart-bar',
     *            ],
     *        ],
     *    ]);
     *
     * 3. Replace defaults entirely:
     *    return [
     *        'title' => 'Custom Title',
     *        'navItems' => [...],
     *    ];
     *
     * 4. Remove specific items:
     *    return HeaderConfig::merge([
     *        'navItems' => [
     *            'tools' => null, // Removes tools from nav
     *        ],
     *        'actions' => null, // Removes all actions
     *    ]);
     *
     * 5. Minimal header (brand only):
     *    return HeaderConfig::getMinimal();
     *
     * @return array
     */
    protected function getHeaderContext(): array
    {
        return HeaderConfig::getDefaults();
    }

    /**
     * Returns the context array used for rendering the body template.
     *
     * @return array
     */
    protected function getBodyContext(): array
    {
        return [];
    }

    /**
     * Returns the context array used for rendering the footer template.
     *
     * @return array
     */
    protected function getFooterContext(): array
    {
        return [];
    }

    /**
     * Singleton-aware callback for WordPress menu pages.
     *
     * @return void
     */
    public function renderPage(): void
    {
        $this->render();
    }
}
