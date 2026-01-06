<?php

namespace SiteAlerts\Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TableSchema
 *
 * Fluent interface for building database table schemas.
 *
 * @package SiteAlerts\Database
 * @version 1.0.0
 */
class TableSchema
{
    /**
     * Table name (without prefix)
     *
     * @var string
     */
    private string $name;

    /**
     * Table columns
     *
     * @var array
     */
    private array $columns = [];

    /**
     * Current column being defined
     *
     * @var string|null
     */
    private ?string $currentColumn = null;

    /**
     * Primary key column
     *
     * @var string|null
     */
    private ?string $primaryKey = null;

    /**
     * Table indexes
     *
     * @var array
     */
    private array $indexes = [];

    /**
     * Unique indexes
     *
     * @var array
     */
    private array $uniqueIndexes = [];

    /**
     * Foreign keys
     *
     * @var array
     */
    private array $foreignKeys = [];

    /**
     * TableSchema constructor.
     *
     * @param string $name Table name (without prefix).
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Add an auto-incrementing primary key column.
     *
     * @param string $name Column name.
     * @return self
     */
    public function id(string $name = 'id'): self
    {
        $this->columns[$name] = [
            'type'           => 'BIGINT',
            'length'         => 20,
            'unsigned'       => true,
            'nullable'       => false,
            'auto_increment' => true,
        ];
        $this->primaryKey     = $name;
        $this->currentColumn  = $name;

        return $this;
    }

    /**
     * Add a BIGINT column.
     *
     * @param string $name Column name.
     * @param bool $unsigned Whether unsigned.
     * @return self
     */
    public function bigInt(string $name, bool $unsigned = true): self
    {
        $this->columns[$name] = [
            'type'     => 'BIGINT',
            'length'   => 20,
            'unsigned' => $unsigned,
            'nullable' => false,
        ];
        $this->currentColumn  = $name;

        return $this;
    }

    /**
     * Add an INT column.
     *
     * @param string $name Column name.
     * @param bool $unsigned Whether unsigned.
     * @return self
     */
    public function int(string $name, bool $unsigned = true): self
    {
        $this->columns[$name] = [
            'type'     => 'INT',
            'length'   => 11,
            'unsigned' => $unsigned,
            'nullable' => false,
        ];
        $this->currentColumn  = $name;

        return $this;
    }

    /**
     * Add a TINYINT column.
     *
     * @param string $name Column name.
     * @param bool $unsigned Whether unsigned.
     * @return self
     */
    public function tinyInt(string $name, bool $unsigned = true): self
    {
        $this->columns[$name] = [
            'type'     => 'TINYINT',
            'length'   => 4,
            'unsigned' => $unsigned,
            'nullable' => false,
        ];
        $this->currentColumn  = $name;

        return $this;
    }

    /**
     * Add a VARCHAR column.
     *
     * @param string $name Column name.
     * @param int $length Maximum length.
     * @return self
     */
    public function varchar(string $name, int $length = 255): self
    {
        $this->columns[$name] = [
            'type'     => 'VARCHAR',
            'length'   => $length,
            'nullable' => false,
        ];
        $this->currentColumn  = $name;

        return $this;
    }

    /**
     * Add a TEXT column.
     *
     * @param string $name Column name.
     * @return self
     */
    public function text(string $name): self
    {
        $this->columns[$name] = [
            'type'     => 'TEXT',
            'nullable' => false,
        ];
        $this->currentColumn  = $name;

        return $this;
    }

    /**
     * Add a LONGTEXT column.
     *
     * @param string $name Column name.
     * @return self
     */
    public function longText(string $name): self
    {
        $this->columns[$name] = [
            'type'     => 'LONGTEXT',
            'nullable' => false,
        ];
        $this->currentColumn  = $name;

        return $this;
    }

    /**
     * Add a DATETIME column.
     *
     * @param string $name Column name.
     * @return self
     */
    public function datetime(string $name): self
    {
        $this->columns[$name] = [
            'type'     => 'DATETIME',
            'nullable' => false,
        ];
        $this->currentColumn  = $name;

        return $this;
    }

    /**
     * Add a DATE column.
     *
     * @param string $name Column name.
     * @return self
     */
    public function date(string $name): self
    {
        $this->columns[$name] = [
            'type'     => 'DATE',
            'nullable' => false,
        ];
        $this->currentColumn  = $name;

        return $this;
    }

    /**
     * Add a TIMESTAMP column.
     *
     * @param string $name Column name.
     * @return self
     */
    public function timestamp(string $name): self
    {
        $this->columns[$name] = [
            'type'     => 'TIMESTAMP',
            'nullable' => false,
            'default'  => 'CURRENT_TIMESTAMP',
        ];
        $this->currentColumn  = $name;

        return $this;
    }

    /**
     * Add a BOOLEAN column (TINYINT(1)).
     *
     * @param string $name Column name.
     * @return self
     */
    public function boolean(string $name): self
    {
        $this->columns[$name] = [
            'type'     => 'TINYINT',
            'length'   => 1,
            'unsigned' => true,
            'nullable' => false,
            'default'  => 0,
        ];
        $this->currentColumn  = $name;

        return $this;
    }

    /**
     * Add a DECIMAL column.
     *
     * @param string $name Column name.
     * @param int $precision Total digits.
     * @param int $scale Decimal places.
     * @return self
     */
    public function decimal(string $name, int $precision = 10, int $scale = 2): self
    {
        $this->columns[$name] = [
            'type'      => 'DECIMAL',
            'precision' => $precision,
            'scale'     => $scale,
            'nullable'  => false,
        ];
        $this->currentColumn  = $name;

        return $this;
    }

    /**
     * Add a JSON column (LONGTEXT for compatibility).
     *
     * @param string $name Column name.
     * @return self
     */
    public function json(string $name): self
    {
        $this->columns[$name] = [
            'type'     => 'LONGTEXT',
            'nullable' => true,
        ];
        $this->currentColumn  = $name;

        return $this;
    }

    /**
     * Make the current column nullable.
     *
     * @return self
     */
    public function nullable(): self
    {
        if ($this->currentColumn) {
            $this->columns[$this->currentColumn]['nullable'] = true;
        }

        return $this;
    }

    /**
     * Set a default value for the current column.
     *
     * @param mixed $value Default value.
     * @return self
     */
    public function default($value): self
    {
        if ($this->currentColumn) {
            $this->columns[$this->currentColumn]['default'] = $value;
        }

        return $this;
    }

    /**
     * Make the current column unsigned.
     *
     * @return self
     */
    public function unsigned(): self
    {
        if ($this->currentColumn) {
            $this->columns[$this->currentColumn]['unsigned'] = true;
        }

        return $this;
    }

    /**
     * Set the primary key.
     *
     * @param string $column Column name.
     * @return self
     */
    public function primaryKey(string $column): self
    {
        $this->primaryKey = $column;

        return $this;
    }

    /**
     * Add an index.
     *
     * @param string $name Index name.
     * @param array $columns Column names.
     * @return self
     */
    public function index(string $name, array $columns): self
    {
        $this->indexes[$name] = $columns;

        return $this;
    }

    /**
     * Add a unique index.
     *
     * @param string $name Index name.
     * @param array $columns Column names.
     * @return self
     */
    public function unique(string $name, array $columns): self
    {
        $this->uniqueIndexes[$name] = $columns;

        return $this;
    }

    /**
     * Add a foreign key.
     *
     * @param string $column Local column.
     * @param string $referenceTable Referenced table.
     * @param string $referenceColumn Referenced column.
     * @return self
     */
    public function foreignKey(string $column, string $referenceTable, string $referenceColumn = 'id'): self
    {
        $this->foreignKeys[] = [
            'column'           => $column,
            'reference_table'  => $referenceTable,
            'reference_column' => $referenceColumn,
        ];

        return $this;
    }

    /**
     * Add created_at and updated_at timestamp columns.
     *
     * @return self
     */
    public function timestamps(): self
    {
        $this->columns['created_at'] = [
            'type'     => 'DATETIME',
            'nullable' => false,
            'default'  => 'CURRENT_TIMESTAMP',
        ];

        $this->columns['updated_at'] = [
            'type'     => 'DATETIME',
            'nullable' => true,
        ];

        return $this;
    }

    /**
     * Get the table name (without prefix).
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the full table name with WordPress prefix.
     *
     * @return string
     */
    public function getFullName(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'sa_' . $this->name;
    }

    /**
     * Build the SQL CREATE TABLE statement.
     *
     * @return string
     */
    public function toSql(): string
    {
        global $wpdb;

        $charset = $wpdb->charset ?? 'utf8mb4';
        $collate = $wpdb->collate ?? 'utf8mb4_unicode_ci';

        $tableName = $this->getFullName();
        $lines     = [];

        // Build column definitions
        foreach ($this->columns as $name => $definition) {
            $lines[] = $this->buildColumnSql($name, $definition);
        }

        // Add primary key
        if ($this->primaryKey) {
            $lines[] = "PRIMARY KEY ({$this->primaryKey})";
        }

        // Add indexes
        foreach ($this->indexes as $name => $columns) {
            $columnList = implode(', ', $columns);
            $lines[]    = "INDEX {$name} ({$columnList})";
        }

        // Add unique indexes
        foreach ($this->uniqueIndexes as $name => $columns) {
            $columnList = implode(', ', $columns);
            $lines[]    = "UNIQUE KEY {$name} ({$columnList})";
        }

        $columnsSql = implode(",\n  ", $lines);

        return "CREATE TABLE {$tableName} (\n  {$columnsSql}\n) DEFAULT CHARACTER SET {$charset} COLLATE {$collate};";
    }

    /**
     * Build SQL for a single column.
     *
     * @param string $name Column name.
     * @param array $definition Column definition.
     * @return string
     */
    private function buildColumnSql(string $name, array $definition): string
    {
        $sql = $name . ' ';

        // Type with length/precision
        if (isset($definition['precision']) && isset($definition['scale'])) {
            $sql .= "{$definition['type']}({$definition['precision']},{$definition['scale']})";
        } elseif (isset($definition['length'])) {
            $sql .= "{$definition['type']}({$definition['length']})";
        } else {
            $sql .= $definition['type'];
        }

        // Unsigned
        if (!empty($definition['unsigned'])) {
            $sql .= ' UNSIGNED';
        }

        // Nullable
        $sql .= $definition['nullable'] ? ' NULL' : ' NOT NULL';

        // Auto increment
        if (!empty($definition['auto_increment'])) {
            $sql .= ' AUTO_INCREMENT';
        }

        // Default value
        if (array_key_exists('default', $definition)) {
            $default = $definition['default'];
            if ($default === 'CURRENT_TIMESTAMP') {
                $sql .= ' DEFAULT CURRENT_TIMESTAMP';
            } elseif (is_null($default)) {
                $sql .= ' DEFAULT NULL';
            } elseif (is_bool($default)) {
                $sql .= ' DEFAULT ' . ($default ? '1' : '0');
            } elseif (is_numeric($default)) {
                $sql .= ' DEFAULT ' . $default;
            } else {
                $sql .= " DEFAULT '" . esc_sql($default) . "'";
            }
        }

        return $sql;
    }
}
