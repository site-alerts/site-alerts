<?php

namespace SiteAlerts\Abstracts;

use Exception;
use SiteAlerts\Database\DatabaseManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AbstractModel
 *
 * Base model class providing CRUD operations and data access.
 * All model classes should extend this class.
 *
 * @package SiteAlerts\Abstracts
 * @version 1.0.0
 */
abstract class AbstractModel
{
    /**
     * Table name (without prefix)
     *
     * @var string
     */
    protected static string $table = '';

    /**
     * Primary key column name
     *
     * @var string
     */
    protected static string $primaryKey = 'id';

    /**
     * Fillable fields (allowed for mass assignment)
     *
     * @var array
     */
    protected static array $fillable = [];

    /**
     * Hidden fields (excluded from toArray/toJson)
     *
     * @var array
     */
    protected static array $hidden = [];

    /**
     * Attribute type casts
     *
     * @var array
     */
    protected static array $casts = [];

    /**
     * Model attributes (data)
     *
     * @var array
     */
    protected array $attributes = [];

    /**
     * Original attributes (for dirty checking)
     *
     * @var array
     */
    protected array $original = [];

    /**
     * Whether the model exists in the database
     *
     * @var bool
     */
    protected bool $exists = false;

    /**
     * AbstractModel constructor.
     *
     * @param array $attributes Initial attributes.
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Create a new model instance.
     *
     * @param array $attributes Initial attributes.
     * @return static
     */
    public static function make(array $attributes = []): self
    {
        return new static($attributes);
    }

    /**
     * Create a new record in the database.
     *
     * @param array $attributes Attributes to save.
     * @return static|null The created model or null on failure.
     */
    public static function create(array $attributes): ?self
    {
        $model = new static($attributes);

        if ($model->save()) {
            return $model;
        }

        return null;
    }

    /**
     * Find a record by primary key.
     *
     * @param int $id Primary key value.
     * @return static|null The model or null if not found.
     */
    public static function find(int $id): ?self
    {
        $row = DatabaseManager::getRow(static::$table, $id, static::$primaryKey);

        if ($row === null) {
            return null;
        }

        return static::hydrate($row);
    }

    /**
     * Find a record by primary key or throw exception.
     *
     * @param int $id Primary key value.
     * @return static
     * @throws Exception If not found.
     */
    public static function findOrFail(int $id): self
    {
        $model = static::find($id);

        if ($model === null) {
            throw new Exception(sprintf('Model not found with ID: %d', esc_html($id)));
        }

        return $model;
    }

    /**
     * Find a record by a specific column value.
     *
     * @param string $column Column name.
     * @param mixed $value Column value.
     * @return static|null The model or null if not found.
     */
    public static function findBy(string $column, $value): ?self
    {
        $rows = DatabaseManager::getRows(static::$table, [
            'where' => [$column => $value],
            'limit' => 1,
        ]);

        if (empty($rows)) {
            return null;
        }

        return static::hydrate($rows[0]);
    }

    /**
     * Get all records.
     *
     * @param array $options Query options (where, orderby, order, limit, offset).
     * @return array Array of model instances.
     */
    public static function all(array $options = []): array
    {
        $rows = DatabaseManager::getRows(static::$table, $options);

        return array_map(static function ($row) {
            return static::hydrate($row);
        }, $rows);
    }

    /**
     * Get records with conditions.
     *
     * @param array $where Where conditions.
     * @param array $options Additional query options.
     * @return array Array of model instances.
     */
    public static function where(array $where, array $options = []): array
    {
        $options['where'] = $where;

        return static::all($options);
    }

    /**
     * Get the first record matching conditions.
     *
     * @param array $where Where conditions.
     * @return static|null The model or null if not found.
     */
    public static function first(array $where = []): ?self
    {
        $results = static::where($where, ['limit' => 1]);

        return $results[0] ?? null;
    }

    /**
     * Count records.
     *
     * @param array $where Optional where conditions.
     * @return int Record count.
     */
    public static function count(array $where = []): int
    {
        return DatabaseManager::getRowCount(static::$table, $where);
    }

    /**
     * Check if a record exists.
     *
     * @param array $where Where conditions.
     * @return bool
     */
    public static function exists(array $where): bool
    {
        return static::count($where) > 0;
    }

    /**
     * Hydrate a model from a database row.
     *
     * @param object $row Database row object.
     * @return static
     */
    protected static function hydrate(object $row): self
    {
        $model             = new static();
        $model->attributes = (array)$row;
        $model->original   = $model->attributes;
        $model->exists     = true;

        return $model;
    }

    /**
     * Fill model with attributes.
     *
     * @param array $attributes Attributes to fill.
     * @return self
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    /**
     * Check if an attribute is fillable.
     *
     * @param string $key Attribute name.
     * @return bool
     */
    protected function isFillable(string $key): bool
    {
        if (empty(static::$fillable)) {
            return true;
        }

        return in_array($key, static::$fillable, true);
    }

    /**
     * Set an attribute value.
     *
     * @param string $key Attribute name.
     * @param mixed $value Attribute value.
     * @return self
     */
    public function setAttribute(string $key, $value): self
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Get an attribute value.
     *
     * @param string $key Attribute name.
     * @param mixed $default Default value if not found.
     * @return mixed
     */
    public function getAttribute(string $key, $default = null)
    {
        $value = $this->attributes[$key] ?? $default;

        return $this->castAttribute($key, $value);
    }

    /**
     * Cast an attribute to its defined type.
     *
     * @param string $key Attribute name.
     * @param mixed $value Attribute value.
     * @return mixed
     */
    protected function castAttribute(string $key, $value)
    {
        if ($value === null) {
            return null;
        }

        $castType = static::$casts[$key] ?? null;

        if ($castType === null) {
            return $value;
        }

        switch ($castType) {
            case 'int':
            case 'integer':
                return (int)$value;

            case 'float':
            case 'double':
            case 'decimal':
                return (float)$value;

            case 'bool':
            case 'boolean':
                return (bool)$value;

            case 'string':
                return (string)$value;

            case 'array':
            case 'json':
                return is_array($value) ? $value : json_decode($value, true);

            default:
                return $value;
        }
    }


    /**
     * Get the primary key value.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        $id = $this->getAttribute(static::$primaryKey);

        return $id !== null ? (int)$id : null;
    }

    /**
     * Get the full table name with prefix.
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return DatabaseManager::getFullTableName(static::$table);
    }

    /**
     * Check if the model has been modified.
     *
     * @return bool
     */
    public function isDirty(): bool
    {
        return $this->attributes !== $this->original;
    }

    /**
     * Get the dirty (changed) attributes.
     *
     * @return array
     */
    public function getDirty(): array
    {
        return array_filter($this->attributes, function ($value, $key) {
            return !array_key_exists($key, $this->original) || $this->original[$key] !== $value;
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Save the model to the database.
     *
     * @return bool Success status.
     */
    public function save(): bool
    {
        if ($this->exists) {
            return $this->performUpdate();
        }

        return $this->performInsert();
    }

    /**
     * Perform an insert operation.
     *
     * @return bool Success status.
     */
    protected function performInsert(): bool
    {
        $attributes = $this->attributes;

        // Add created_at timestamp if not set
        if (!isset($attributes['created_at'])) {
            $attributes['created_at'] = current_time('mysql', true);
        }

        $id = DatabaseManager::insert(static::$table, $attributes);

        if ($id === false) {
            return false;
        }

        $this->setAttribute(static::$primaryKey, $id);
        $this->exists = true;
        $this->syncOriginal();

        return true;
    }

    /**
     * Perform an update operation.
     *
     * @return bool Success status.
     */
    protected function performUpdate(): bool
    {
        if (!$this->isDirty()) {
            return true;
        }

        $dirty = $this->getDirty();

        // Add updated_at timestamp
        $dirty['updated_at'] = current_time('mysql', true);

        $result = DatabaseManager::update(
            static::$table,
            $dirty,
            [static::$primaryKey => $this->getId()]
        );

        if ($result === false) {
            return false;
        }

        $this->setAttribute('updated_at', $dirty['updated_at']);
        $this->syncOriginal();

        return true;
    }

    /**
     * Sync original attributes with current.
     *
     * @return self
     */
    protected function syncOriginal(): self
    {
        $this->original = $this->attributes;

        return $this;
    }

    /**
     * Delete the model from the database.
     *
     * @return bool Success status.
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $result = DatabaseManager::delete(
            static::$table,
            [static::$primaryKey => $this->getId()]
        );

        if ($result === false) {
            return false;
        }

        $this->exists = false;

        return true;
    }

    /**
     * Refresh the model from the database.
     *
     * @return self
     */
    public function refresh(): self
    {
        if (!$this->exists) {
            return $this;
        }

        $fresh = static::find($this->getId());

        if ($fresh !== null) {
            $this->attributes = $fresh->attributes;
            $this->syncOriginal();
        }

        return $this;
    }

    /**
     * Get all attributes as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $attributes = $this->attributes;

        // Remove hidden attributes
        foreach (static::$hidden as $key) {
            unset($attributes[$key]);
        }

        return $attributes;
    }

    /**
     * Get all attributes as JSON.
     *
     * @param int $options JSON encode options.
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return wp_json_encode($this->toArray(), $options);
    }

    /**
     * Magic getter for attributes.
     *
     * @param string $key Attribute name.
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Magic setter for attributes.
     *
     * @param string $key Attribute name.
     * @param mixed $value Attribute value.
     * @return void
     */
    public function __set(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Magic isset check for attributes.
     *
     * @param string $key Attribute name.
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Convert model to string (JSON).
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }
}
