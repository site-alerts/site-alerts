<?php

namespace SiteAlerts\Database;

use SiteAlerts\Abstracts\AbstractSingleton;
use SiteAlerts\Database\Schemas\CoreTables;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SchemaManager
 *
 * Manages database schema registration at boot time.
 * Allows child plugins to register their own schemas via filter.
 *
 * @package SiteAlerts\Database
 * @version 1.0.0
 */
class SchemaManager extends AbstractSingleton
{
    /**
     * Schema provider classes.
     *
     * @var array<class-string>
     */
    private array $providers = [
        CoreTables::class,
    ];

    /**
     * Register all table schemas.
     *
     * @return void
     */
    public function register(): void
    {
        /**
         * Filter schema providers before registration.
         * Child plugins can add their own schema providers.
         *
         * @param array $providers Schema provider class names.
         */
        $providers = apply_filters('site_alerts_schema_providers', $this->providers);

        foreach ($providers as $providerClass) {
            $this->registerProvider($providerClass);
        }
    }

    /**
     * Register schemas from a provider.
     *
     * @param string $providerClass The provider class name.
     * @return void
     */
    private function registerProvider(string $providerClass): void
    {
        if (!class_exists($providerClass)) {
            return;
        }

        if (!method_exists($providerClass, 'getSchemas')) {
            return;
        }

        $schemas = $providerClass::getSchemas();

        foreach ($schemas as $schema) {
            DatabaseManager::registerTable($schema);
        }
    }

    /**
     * Get registered providers.
     *
     * @return array
     */
    public function getProviders(): array
    {
        return apply_filters('site_alerts_schema_providers', $this->providers);
    }

    /**
     * Add a schema provider.
     *
     * @param string $providerClass The provider class name.
     * @return self
     */
    public function addProvider(string $providerClass): self
    {
        if (!in_array($providerClass, $this->providers, true)) {
            $this->providers[] = $providerClass;
        }
        return $this;
    }
}
