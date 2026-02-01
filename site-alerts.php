<?php

/**
 * Plugin Name:         Site Alerts
 * Plugin URI:          https://site-alerts.com/
 * Description:         Get notified when something unusual happens on your WordPress site.
 * Version:             1.0.0
 * Author:              Mohammad Yari
 * Author URI:          https://site-alerts.com/
 * Text Domain:         site-alerts
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
 * @var string
 */
if (!defined('SA_SLUG')) {
    define('SA_SLUG', 'site-alerts');
}

/**
 * Main plugin file.
 *
 * Stores the absolute path to the plugin's main file.
 *
 * @var string
 */
if (!defined('SA_PLUGIN_FILE')) {
    define('SA_PLUGIN_FILE', __FILE__);
}

/**
 * Main plugin path.
 *
 * Absolute path to the plugin root folder.
 *
 * @var string
 */
if (!defined('SA_PATH')) {
    define('SA_PATH', plugin_dir_path(SA_PLUGIN_FILE));
}

/**
 * Plugin URL.
 *
 * Stores the absolute URL to the plugin folder.
 *
 * @var string
 */
if (!defined('SA_URL')) {
    define('SA_URL', plugin_dir_url(SA_PLUGIN_FILE));
}

/**
 * Default plugin templates path.
 *
 * Absolute path to the plugin templates folder.
 *
 * @var string
 */
if (!defined('SA_TEMPLATES_PATH')) {
    define('SA_TEMPLATES_PATH', SA_PATH . 'templates/');
}

/**
 * Plugin assets URL.
 *
 * Stores the URL to the plugin's assets folder (CSS, JS, images, etc.).
 *
 * @var string
 */
if (!defined('SA_ASSETS')) {
    define('SA_ASSETS', SA_URL . 'assets/');
}

/**
 * Plugin version.
 *
 * Used for cache-busting scripts/styles and version checks.
 *
 * @var string
 */
if (!defined('SA_VERSION')) {
    define('SA_VERSION', '1.0.0');
}

/**
 * Autoload all classes using Composer autoloader.
 */
require_once __DIR__ . '/vendor/autoload.php';

use SiteAlerts\Core;
use SiteAlerts\Lifecycle\ActivationHandler;
use SiteAlerts\Lifecycle\DeactivationHandler;

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
if (!function_exists('siteAlerts')) {
    function siteAlerts(): ?Core
    {
        return Core::getInstance();
    }
}

/**
 * Initialize the plugin by creating the main instance.
 *
 * The actual init logic should be handled inside Core class.
 */
siteAlerts();
