<?php

/**
 * Plugin Name:         Proactive Site Advisor
 * Plugin URI:          https://github.com/proactive-site-advisor/proactive-site-advisor
 * Description:         Provides proactive insights and actionable recommendations for WordPress sites, alerting you to potential issues before they impact your site.
 * Version:             1.0.0
 * Author:              Mohammad Yari
 * Author URI:          https://github.com/proactive-site-advisor
 * Text Domain:         proactive-site-advisor
 * Domain Path:         /languages
 * Requires at least:   6.1
 * Requires PHP:        7.4
 * License:             GPL-2.0-or-later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 */

# Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Plugin slug.
 *
 * Used for text domains, options, or unique identifiers.
 *
 * @const string
 */
if (!defined('PROACTIVE_SITE_ADVISOR_SLUG')) {
    define('PROACTIVE_SITE_ADVISOR_SLUG', 'proactive-site-advisor');
}

/**
 * Main plugin file.
 *
 * Stores the absolute path to the plugin's main file.
 *
 * @const string
 */
if (!defined('PROACTIVE_SITE_ADVISOR_PLUGIN_FILE')) {
    define('PROACTIVE_SITE_ADVISOR_PLUGIN_FILE', __FILE__);
}

/**
 * Main plugin path.
 *
 * Absolute path to the plugin root folder.
 *
 * @const string
 */
if (!defined('PROACTIVE_SITE_ADVISOR_PATH')) {
    define('PROACTIVE_SITE_ADVISOR_PATH', plugin_dir_path(PROACTIVE_SITE_ADVISOR_PLUGIN_FILE));
}

/**
 * Plugin URL.
 *
 * Stores the absolute URL to the plugin folder.
 *
 * @const string
 */
if (!defined('PROACTIVE_SITE_ADVISOR_URL')) {
    define('PROACTIVE_SITE_ADVISOR_URL', plugin_dir_url(PROACTIVE_SITE_ADVISOR_PLUGIN_FILE));
}

/**
 * Default plugin templates path.
 *
 * Absolute path to the plugin templates folder.
 *
 * @const string
 */
if (!defined('PROACTIVE_SITE_ADVISOR_TEMPLATES_PATH')) {
    define('PROACTIVE_SITE_ADVISOR_TEMPLATES_PATH', PROACTIVE_SITE_ADVISOR_PATH . 'templates/');
}

/**
 * Plugin assets URL.
 *
 * Stores the URL to the plugin's assets folder (CSS, JS, images, etc.).
 *
 * @const string
 */
if (!defined('PROACTIVE_SITE_ADVISOR_ASSETS')) {
    define('PROACTIVE_SITE_ADVISOR_ASSETS', PROACTIVE_SITE_ADVISOR_URL . 'assets/');
}

/**
 * Plugin version.
 *
 * Used for cache-busting scripts/styles and version checks.
 *
 * @const string
 */
if (!defined('PROACTIVE_SITE_ADVISOR_VERSION')) {
    define('PROACTIVE_SITE_ADVISOR_VERSION', '1.0.0');
}

/**
 * Autoload all classes using Composer autoloader.
 */
require_once __DIR__ . '/vendor/autoload.php';

use ProactiveSiteAdvisor\Core;
use ProactiveSiteAdvisor\Lifecycle\ActivationHandler;
use ProactiveSiteAdvisor\Lifecycle\DeactivationHandler;

/**
 * Register activation hook.
 *
 * Handles plugin activation tasks like creating database tables,
 * setting default options, and flushing rewrite rules.
 */
ActivationHandler::register();

/**
 * Register deactivation hook.
 *
 * Handles plugin deactivation tasks like clearing scheduled events
 * and cleaning up transients.
 */
DeactivationHandler::register();

/**
 * Returns the main instance of the Starter Plugin Core class.
 *
 * Acts as a helper function to access the singleton instance anywhere in the plugin.
 *
 * @return Core Main plugin instance.
 */
if (!function_exists('proactiveSiteAdvisor')) {
    function proactiveSiteAdvisor(): ?Core
    {
        return Core::getInstance();
    }
}

/**
 * Initialize the plugin by creating the main instance.
 *
 * The actual init logic should be handled inside Core class.
 */
proactiveSiteAdvisor();
