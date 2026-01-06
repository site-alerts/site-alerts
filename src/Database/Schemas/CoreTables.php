<?php

namespace SiteAlerts\Database\Schemas;

use SiteAlerts\Database\DatabaseManager;
use SiteAlerts\Database\TableSchema;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CoreTables
 *
 * Defines core database tables for the Site Alerts plugin.
 *
 * @package SiteAlerts\Database\Schemas
 * @version 1.0.0
 */
class CoreTables
{
    /**
     * Register table creation hooks.
     *
     * @return void
     */
    public static function register(): void
    {
        add_action('site_alerts_create_tables', [self::class, 'createTables']);
    }

    /**
     * Get all table schemas for this provider.
     *
     * @return TableSchema[]
     */
    public static function getSchemas(): array
    {
        return [
            self::getDailyStatsSchema(),
            self::getAlertsSchema(),
        ];
    }

    /**
     * Register and create all core tables.
     *
     * @return void
     */
    public static function createTables(): void
    {
        DatabaseManager::registerTable(self::getDailyStatsSchema());
        DatabaseManager::registerTable(self::getAlertsSchema());

        DatabaseManager::createTables();
    }

    /**
     * Daily statistics table schema.
     *
     * @return TableSchema
     */
    public static function getDailyStatsSchema(): TableSchema
    {
        $schema = new TableSchema('daily_stats');
        $schema
            ->id()
            ->date('stats_date')
            ->int('pageviews')->default(0)
            ->int('errors_404')->default(0)
            ->json('top_404_json')->nullable()
            ->timestamps()
            ->unique('daily_stats_unique', ['stats_date']);

        return $schema;
    }

    /**
     * Alerts table schema.
     *
     * @return TableSchema
     */
    public static function getAlertsSchema(): TableSchema
    {
        $schema = new TableSchema('alerts');
        $schema
            ->id()
            ->date('alert_date')
            ->varchar('type', 40)
            ->varchar('severity', 12)
            ->varchar('title', 190)
            ->text('message')
            ->json('meta_json')
            ->timestamps()
            ->unique('alerts_unique', ['alert_date', 'type']);

        return $schema;
    }
}