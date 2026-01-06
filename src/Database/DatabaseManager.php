<?php

namespace SiteAlerts\Database;

use SiteAlerts\Utils\Logger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class DatabaseManager
 *
 * Manages custom database table creation and updates using dbDelta().
 *
 * @package SiteAlerts\Database
 * @version 1.0.0
 */
class DatabaseManager
{
    /**
     * Option name for storing database version
     */
    public const VERSION_OPTION = 'sa_db_version';

    /**
     * Current database schema version
     *
     * @var string
     */
    private static string $version = '1.0.0';

    /**
     * Registered table schemas
     *
     * @var TableSchema[]
     */
    private static array $tables = [];

    /**
     * Register a table schema.
     *
     * @param TableSchema $schema The table schema.
     * @return void
     */
    public static function registerTable(TableSchema $schema): void
    {
        self::$tables[$schema->getName()] = $schema;
    }

    /**
     * Register multiple table schemas.
     *
     * @param TableSchema[] $schemas Array of table schemas.
     * @return void
     */
    public static function registerTables(array $schemas): void
    {
        foreach ($schemas as $schema) {
            self::registerTable($schema);
        }
    }

    /**
     * Get a registered table schema.
     *
     * @param string $name Table name (without prefix).
     * @return TableSchema|null
     */
    public static function getTable(string $name): ?TableSchema
    {
        return self::$tables[$name] ?? null;
    }

    /**
     * Get all registered table schemas.
     *
     * @return TableSchema[]
     */
    public static function getTables(): array
    {
        return self::$tables;
    }

    /**
     * Set the database schema version.
     *
     * @param string $version Version string.
     * @return void
     */
    public static function setVersion(string $version): void
    {
        self::$version = $version;
    }

    /**
     * Get the current database schema version.
     *
     * @return string
     */
    public static function getVersion(): string
    {
        return self::$version;
    }

    /**
     * Get the installed database version.
     *
     * @return string
     */
    public static function getInstalledVersion(): string
    {
        return get_option(self::VERSION_OPTION, '0.0.0');
    }

    /**
     * Check if database needs update.
     *
     * @return bool
     */
    public static function needsUpdate(): bool
    {
        return version_compare(self::getInstalledVersion(), self::$version, '<');
    }

    /**
     * Create or update all registered tables.
     *
     * @return array Results with table names as keys.
     */
    public static function createTables(): array
    {
        /**
         * Filter the table schemas before creation.
         *
         * @param TableSchema[] $tables Registered table schemas.
         */
        $tables = apply_filters('site_alerts_database_tables', self::$tables);

        if (empty($tables)) {
            return [];
        }

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $results = [];

        foreach ($tables as $schema) {
            $results[$schema->getName()] = self::createTable($schema);
        }

        // Update version after successful creation
        update_option(self::VERSION_OPTION, self::$version);

        /**
         * Action fired after database tables are created/updated.
         *
         * @param array $results Creation results.
         */
        do_action('site_alerts_database_tables_created', $results);

        return $results;
    }

    /**
     * Create or update a single table.
     *
     * @param TableSchema $schema The table schema.
     * @return array dbDelta result.
     */
    public static function createTable(TableSchema $schema): array
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = $schema->toSql();

        Logger::debug('Creating database table', [
            'table' => $schema->getFullName(),
            'sql'   => $sql,
        ]);

        $result = dbDelta($sql);

        if (!empty($result)) {
            Logger::info('Database table created/updated', [
                'table'  => $schema->getFullName(),
                'result' => $result,
            ]);
        }

        return $result;
    }

    /**
     * Drop a table.
     *
     * @param string $name Table name (without prefix).
     * @return bool
     */
    public static function dropTable(string $name): bool
    {
        global $wpdb;

        $schema = self::getTable($name);

        if (!$schema) {
            return false;
        }

        $tableName = $schema->getFullName();

        Logger::warning('Dropping database table', ['table' => $tableName]);

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom table management requires direct queries
        $result = $wpdb->query("DROP TABLE IF EXISTS {$tableName}");

        return $result !== false;
    }

    /**
     * Drop all registered tables.
     *
     * @return void
     */
    public static function dropAllTables(): void
    {
        foreach (self::$tables as $schema) {
            self::dropTable($schema->getName());
        }

        delete_option(self::VERSION_OPTION);
    }

    /**
     * Check if a table exists.
     *
     * @param string $name Table name (without prefix).
     * @return bool
     */
    public static function tableExists(string $name): bool
    {
        global $wpdb;

        $schema = self::getTable($name);

        if (!$schema) {
            return false;
        }

        $tableName = $schema->getFullName();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom table management requires direct queries
        return $wpdb->get_var("SHOW TABLES LIKE '{$tableName}'") === $tableName;
    }

    /**
     * Get table row count.
     *
     * @param string $name Table name (without prefix).
     * @return int
     */
    public static function getRowCount(string $name): int
    {
        global $wpdb;

        $schema = self::getTable($name);

        if (!$schema) {
            return 0;
        }

        $tableName = $schema->getFullName();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom table management requires direct queries
        return (int)$wpdb->get_var("SELECT COUNT(*) FROM {$tableName}");
    }

    /**
     * Truncate a table.
     *
     * @param string $name Table name (without prefix).
     * @return bool
     */
    public static function truncateTable(string $name): bool
    {
        global $wpdb;

        $schema = self::getTable($name);

        if (!$schema) {
            return false;
        }

        $tableName = $schema->getFullName();

        Logger::warning('Truncating database table', ['table' => $tableName]);

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is sanitized via TableSchema::getFullName()
        $result = $wpdb->query("TRUNCATE TABLE {$tableName}");

        return $result !== false;
    }

    /**
     * Get table columns.
     *
     * @param string $name Table name (without prefix).
     * @return array
     */
    public static function getTableColumns(string $name): array
    {
        global $wpdb;

        $schema = self::getTable($name);

        if (!$schema) {
            return [];
        }

        $tableName = $schema->getFullName();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom table management requires direct queries
        $columns = $wpdb->get_results("DESCRIBE {$tableName}", ARRAY_A);

        return $columns ?: [];
    }

    /**
     * Check if a column exists in a table.
     *
     * @param string $tableName Table name (without prefix).
     * @param string $columnName Column name.
     * @return bool
     */
    public static function columnExists(string $tableName, string $columnName): bool
    {
        $columns = self::getTableColumns($tableName);

        foreach ($columns as $column) {
            if ($column['Field'] === $columnName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Run a database migration.
     *
     * @param string $fromVersion Version to migrate from.
     * @param callable $callback Migration callback.
     * @return bool Whether migration was run.
     */
    public static function migrate(string $fromVersion, callable $callback): bool
    {
        $installedVersion = self::getInstalledVersion();

        if (version_compare($installedVersion, $fromVersion, '<')) {
            return false;
        }

        Logger::info('Running database migration', [
            'from_version'      => $fromVersion,
            'installed_version' => $installedVersion,
        ]);

        try {
            $callback();
            return true;
        } catch (\Exception $e) {
            Logger::error('Database migration failed', [
                'from_version' => $fromVersion,
                'error'        => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Execute a raw query.
     *
     * @param string $sql SQL query.
     * @return bool|int|\mysqli_result|null Query result.
     */
    public static function query(string $sql)
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Raw query method, caller responsible for escaping
        return $wpdb->query($sql);
    }

    /**
     * Get the full table name with prefix.
     *
     * @param string $name Table name (without prefix).
     * @return string Full table name or empty string if not registered.
     */
    public static function getFullTableName(string $name): string
    {
        $schema = self::getTable($name);
        return $schema ? $schema->getFullName() : '';
    }

    /**
     * Get the last database error.
     *
     * @return string
     */
    public static function getLastError(): string
    {
        global $wpdb;

        return $wpdb->last_error;
    }

    /**
     * Get the last inserted ID.
     *
     * @return int
     */
    public static function getLastInsertId(): int
    {
        global $wpdb;

        return (int)$wpdb->insert_id;
    }

    /**
     * Prepare and execute a query.
     *
     * @param string $sql SQL query with placeholders.
     * @param mixed ...$args Values to substitute.
     * @return bool|int|\mysqli_result|null Query result.
     */
    public static function preparedQuery(string $sql, ...$args)
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Query is prepared with $wpdb->prepare()
        return $wpdb->query($wpdb->prepare($sql, ...$args));
    }

    /**
     * Insert a row into a table.
     *
     * @param string $name Table name (without prefix).
     * @param array $data Data to insert.
     * @param array $format Data format specifiers.
     * @return int|false Insert ID or false on failure.
     */
    public static function insert(string $name, array $data, array $format = [])
    {
        global $wpdb;

        $schema = self::getTable($name);

        if (!$schema) {
            return false;
        }

        $tableName = $schema->getFullName();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table management requires direct queries
        $result = $wpdb->insert($tableName, $data, $format ?: null);

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update rows in a table.
     *
     * @param string $name Table name (without prefix).
     * @param array $data Data to update.
     * @param array $where WHERE conditions.
     * @param array $format Data format specifiers.
     * @param array $whereFormat WHERE format specifiers.
     * @return int|false Number of rows updated or false on failure.
     */
    public static function update(string $name, array $data, array $where, array $format = [], array $whereFormat = [])
    {
        global $wpdb;

        $schema = self::getTable($name);

        if (!$schema) {
            return false;
        }

        $tableName = $schema->getFullName();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table management requires direct queries
        return $wpdb->update($tableName, $data, $where, $format ?: null, $whereFormat ?: null);
    }

    /**
     * Delete rows from a table.
     *
     * @param string $name Table name (without prefix).
     * @param array $where WHERE conditions.
     * @param array $whereFormat WHERE format specifiers.
     * @return int|false Number of rows deleted or false on failure.
     */
    public static function delete(string $name, array $where, array $whereFormat = [])
    {
        global $wpdb;

        $schema = self::getTable($name);

        if (!$schema) {
            return false;
        }

        $tableName = $schema->getFullName();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table management requires direct queries
        return $wpdb->delete($tableName, $where, $whereFormat ?: null);
    }

    /**
     * Get a single row from a table.
     *
     * @param string $name Table name (without prefix).
     * @param int|array $idOrWhere Row ID or WHERE conditions.
     * @param string $idColumn ID column name.
     * @return object|null
     */
    public static function getRow(string $name, $idOrWhere, string $idColumn = 'id'): ?object
    {
        global $wpdb;

        $schema = self::getTable($name);

        if (!$schema) {
            return null;
        }

        $tableName = $schema->getFullName();

        if (is_array($idOrWhere)) {
            $conditions = [];
            $values     = [];

            foreach ($idOrWhere as $column => $value) {
                $conditions[] = "`{$column}` = %s";
                $values[]     = $value;
            }

            $whereClause = implode(' AND ', $conditions);

            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is sanitized, placeholders built dynamically
            $sql = $wpdb->prepare("SELECT * FROM {$tableName} WHERE {$whereClause} LIMIT 1", ...$values);
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is sanitized, value is prepared
            $sql = $wpdb->prepare("SELECT * FROM {$tableName} WHERE `{$idColumn}` = %s LIMIT 1", $idOrWhere);
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- SQL is prepared above
        return $wpdb->get_row($sql);
    }

    /**
     * Get multiple rows from a table.
     *
     * @param string $name Table name (without prefix).
     * @param array $args Query arguments.
     * @return array
     */
    public static function getRows(string $name, array $args = []): array
    {
        global $wpdb;

        $schema = self::getTable($name);

        if (!$schema) {
            return [];
        }

        $tableName = $schema->getFullName();

        $defaults = [
            'where'   => [],
            'orderby' => 'id',
            'order'   => 'ASC',
            'limit'   => 0,
            'offset'  => 0,
            'columns' => '*',
        ];

        $args = wp_parse_args($args, $defaults);

        $columns = is_array($args['columns']) ? implode(', ', $args['columns']) : $args['columns'];
        $sql     = "SELECT {$columns} FROM {$tableName}";

        // WHERE
        if (!empty($args['where'])) {
            $conditions = [];
            $values     = [];

            foreach ($args['where'] as $column => $value) {
                if (is_array($value)) {
                    $placeholders = array_fill(0, count($value), '%s');
                    $conditions[] = "`{$column}` IN (" . implode(', ', $placeholders) . ")";
                    $values       = array_merge($values, $value);
                } else {
                    $conditions[] = "`{$column}` = %s";
                    $values[]     = $value;
                }
            }

            $sql .= ' WHERE ' . implode(' AND ', $conditions);

            if (!empty($values)) {
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- SQL is prepared with values
                $sql = $wpdb->prepare($sql, ...$values);
            }
        }

        // ORDER BY
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- orderby is from trusted defaults, order is validated
        $sql .= " ORDER BY `{$args['orderby']}` {$order}";

        // LIMIT
        if ($args['limit'] > 0) {
            $sql .= $wpdb->prepare(' LIMIT %d', $args['limit']);

            if ($args['offset'] > 0) {
                $sql .= $wpdb->prepare(' OFFSET %d', $args['offset']);
            }
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- SQL is built with prepared statements
        return $wpdb->get_results($sql);
    }
}
