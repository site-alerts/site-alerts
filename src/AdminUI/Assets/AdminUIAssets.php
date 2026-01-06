<?php

namespace SiteAlerts\AdminUI\Assets;

use SiteAlerts\Abstracts\AbstractSingleton;
use SiteAlerts\Components\AssetsComponent;
use SiteAlerts\Config\PrefixConfig;
use SiteAlerts\Components\AjaxComponent;
use SiteAlerts\AdminUI\Theme\ThemeManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AdminUIAssets
 *
 * Handles the registration and enqueueing of AdminUI assets including
 * core styles/scripts and third-party vendor libraries.
 *
 * @package SiteAlerts\AdminUI\Assets
 * @version 1.0.0
 */
class AdminUIAssets extends AbstractSingleton
{
    /**
     * Registered vendor configurations.
     *
     * @var array
     */
    private array $vendors = [];

    /**
     * Vendors queued for loading.
     *
     * @var array
     */
    private array $queuedVendors = [];

    /**
     * Whether assets have been registered.
     *
     * @var bool
     */
    private bool $registered = false;

    /**
     * Whether core assets have been enqueued.
     *
     * @var bool
     */
    private bool $coreEnqueued = false;

    /**
     * Vendors that have been enqueued.
     *
     * @var array
     */
    private array $enqueuedVendors = [];

    /**
     * Core asset handle.
     */
    private const CORE_HANDLE = 'admin-ui';

    /**
     * Register the assets manager.
     *
     * @return void
     */
    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        $this->registered = true;
        $this->registerVendors();

        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Register available vendor libraries.
     *
     * @return void
     */
    private function registerVendors(): void
    {
        $libsUrl = SA_URL . 'libs/';

        $this->vendors = [];

        // Prepend libs URL to all paths
        foreach ($this->vendors as $key => $config) {
            if (!empty($config['css'])) {
                $this->vendors[$key]['css'] = array_map(
                    static fn($path) => $libsUrl . $path,
                    $config['css']
                );
            }
            if (!empty($config['js'])) {
                $this->vendors[$key]['js'] = array_map(
                    static fn($path) => $libsUrl . $path,
                    $config['js']
                );
            }
        }

        /**
         * Filter the available vendor libraries.
         *
         * @param array $vendors Vendor configurations.
         */
        $this->vendors = apply_filters('site_alerts_admin_ui_vendors', $this->vendors);
    }

    /**
     * Queue vendor libraries for loading.
     *
     * @param string|array $vendors Vendor name(s) to queue.
     * @return self
     */
    public function queue($vendors): self
    {
        $vendors = (array)$vendors;

        foreach ($vendors as $vendor) {
            if (isset($this->vendors[$vendor]) && !in_array($vendor, $this->queuedVendors, true)) {
                $this->queuedVendors[] = $vendor;
            }
        }

        return $this;
    }

    /**
     * Enqueue all queued assets.
     *
     * @return void
     */
    public function enqueueAssets(): void
    {
        $this->enqueueCoreAssets();

        foreach ($this->queuedVendors as $vendor) {
            $this->enqueueVendor($vendor);
        }
    }

    /**
     * Enqueue core AdminUI assets.
     *
     * @return void
     */
    private function enqueueCoreAssets(): void
    {
        // Prevent duplicate enqueuing
        if ($this->coreEnqueued) {
            return;
        }

        $handle = AssetsComponent::getHandle(self::CORE_HANDLE);

        // Check if already enqueued by WordPress
        if (wp_style_is($handle, 'enqueued') || wp_script_is($handle, 'enqueued')) {
            $this->coreEnqueued = true;
            return;
        }

        // Register and enqueue styles
        AssetsComponent::registerStyle(self::CORE_HANDLE, 'css/admin.min.css');
        AssetsComponent::enqueueStyle(self::CORE_HANDLE);

        // Register and enqueue scripts
        AssetsComponent::registerScript(self::CORE_HANDLE, 'js/admin.min.js', ['jquery'], null, true);
        AssetsComponent::enqueueScript(self::CORE_HANDLE);

        // Localize script with config
        AssetsComponent::localizeScript(self::CORE_HANDLE, PrefixConfig::CONFIG_OBJECT, [
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => AjaxComponent::createNonce(),
            'restUrl'   => rest_url('site-alerts/v1/'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'theme'     => $this->getCurrentTheme(),
        ]);

        $this->coreEnqueued = true;
    }

    /**
     * Enqueue a specific vendor library.
     *
     * @param string $vendor Vendor name.
     * @return void
     */
    private function enqueueVendor(string $vendor): void
    {
        // Check if vendor exists and not already enqueued
        if (!isset($this->vendors[$vendor])) {
            return;
        }

        if (in_array($vendor, $this->enqueuedVendors, true)) {
            return;
        }

        $config     = $this->vendors[$vendor];
        $baseHandle = PrefixConfig::HANDLE_PREFIX . 'vendor-' . $vendor;

        // Enqueue CSS files
        if (!empty($config['css'])) {
            foreach ($config['css'] as $index => $url) {
                $handle = $baseHandle . ($index > 0 ? "-{$index}" : '') . '-css';

                if (!wp_style_is($handle, 'enqueued')) {
                    wp_enqueue_style($handle, $url, [], SA_VERSION);
                }
            }
        }

        // Enqueue JS files
        if (!empty($config['js'])) {
            $deps = array_merge(['jquery'], $config['deps'] ?? []);

            foreach ($config['js'] as $index => $url) {
                $handle = $baseHandle . ($index > 0 ? "-{$index}" : '') . '-js';

                if (!wp_script_is($handle, 'enqueued')) {
                    wp_enqueue_script($handle, $url, $deps, SA_VERSION, true);
                }
            }
        }

        $this->enqueuedVendors[] = $vendor;
    }

    /**
     * Get the current user's theme preference.
     *
     * @return string Theme name ('light' or 'dark').
     */
    public function getCurrentTheme(): string
    {
        return ThemeManager::getInstance()->getCurrentTheme();
    }

    /**
     * Static helper to queue vendors.
     *
     * @param string|array $vendors Vendor name(s) to queue.
     * @return void
     */
    public static function enqueue($vendors): void
    {
        self::getInstance()->queue($vendors);
    }

    /**
     * Check if a vendor is available.
     *
     * @param string $vendor Vendor name.
     * @return bool
     */
    public function hasVendor(string $vendor): bool
    {
        return isset($this->vendors[$vendor]);
    }

    /**
     * Get all available vendor names.
     *
     * @return array
     */
    public function getAvailableVendors(): array
    {
        return array_keys($this->vendors);
    }
}
