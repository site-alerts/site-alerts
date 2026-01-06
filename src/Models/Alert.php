<?php

namespace SiteAlerts\Models;

use SiteAlerts\Abstracts\AbstractModel;
use SiteAlerts\Database\DatabaseManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Alert
 *
 * Model for site alerts including warnings, errors, and notifications.
 * Stores one record per date+type combination with severity and messaging.
 *
 * @package SiteAlerts\Models
 * @version 1.0.0
 */
class Alert extends AbstractModel
{
    /**
     * Table name (without prefix)
     *
     * @var string
     */
    protected static string $table = 'alerts';

    /**
     * Fillable fields (allowed for mass assignment)
     *
     * @var array
     */
    protected static array $fillable = [
        'alert_date',
        'type',
        'severity',
        'title',
        'message',
        'meta_json',
    ];

    /**
     * Attribute type casts
     *
     * @var array
     */
    protected static array $casts = [
        'meta_json' => 'json',
    ];

    /**
     * Create an alert if it doesn't already exist for the date+type.
     *
     * @param string $dateYmd Date in Y-m-d format.
     * @param string $type Alert type identifier.
     * @param string $severity Alert severity level.
     * @param string $title Alert title.
     * @param string $message Alert message.
     * @param string|null $metaJson Optional JSON metadata.
     * @return static|null The created model or null if already exists.
     */
    public static function createIfNotExists(
        string  $dateYmd,
        string  $type,
        string  $severity,
        string  $title,
        string  $message,
        ?string $metaJson = null
    ): ?self
    {
        $existing = static::first([
            'alert_date' => $dateYmd,
            'type'       => $type,
        ]);

        if ($existing !== null) {
            return null;
        }

        return static::create([
            'alert_date' => $dateYmd,
            'type'       => $type,
            'severity'   => $severity,
            'title'      => $title,
            'message'    => $message,
            'meta_json'  => $metaJson,
        ]);
    }

    /**
     * Find alerts by date.
     *
     * @param string $dateYmd Date in Y-m-d format.
     * @param array $options Additional query options.
     * @return array Array of Alert instances.
     */
    public static function findByDate(string $dateYmd, array $options = []): array
    {
        return static::where(['alert_date' => $dateYmd], $options);
    }

    /**
     * Find alerts by type.
     *
     * @param string $type Alert type identifier.
     * @param array $options Additional query options.
     * @return array Array of Alert instances.
     */
    public static function findByType(string $type, array $options = []): array
    {
        return static::where(['type' => $type], $options);
    }

    /**
     * Find alerts by severity.
     *
     * @param string $severity Alert severity level.
     * @param array $options Additional query options.
     * @return array Array of Alert instances.
     */
    public static function findBySeverity(string $severity, array $options = []): array
    {
        return static::where(['severity' => $severity], $options);
    }

    /**
     * Delete records older than the given date.
     *
     * @param string $dateYmd Date in Y-m-d format.
     * @return void
     */
    public static function purgeOlderThan(string $dateYmd): void
    {
        $table = static::getTableName();

        DatabaseManager::preparedQuery(
            "DELETE FROM {$table} WHERE alert_date < %s",
            $dateYmd
        );
    }

    /**
     * Delete the record for a specific date and type.
     *
     * @param string $dateYmd Date in Y-m-d format.
     * @param string $type Alert type.
     * @return void
     */
    public static function deleteByDateAndType(string $dateYmd, string $type): void
    {
        $table = static::getTableName();

        DatabaseManager::preparedQuery(
            "DELETE FROM {$table} WHERE alert_date = %s AND type = %s",
            $dateYmd,
            $type
        );
    }
}
