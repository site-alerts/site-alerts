<?php

namespace SiteAlerts\Cache;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class QueryCache
 *
 * Specialized caching for database queries.
 *
 * @package SiteAlerts\Cache
 * @version 1.0.0
 */
class QueryCache
{
    /**
     * Cache group for queries
     */
    public const GROUP = 'site_alerts_query';

    /**
     * Default expiration (15 minutes)
     */
    public const DEFAULT_EXPIRATION = 900;

    /**
     * Cache manager instance
     *
     * @var CacheManager
     */
    private CacheManager $cache;

    /**
     * QueryCache constructor.
     */
    public function __construct()
    {
        $this->cache = CacheManager::getInstance();
    }

    /**
     * Get cached query result or execute query.
     *
     * @param string $sql SQL query.
     * @param callable $callback Callback to execute query.
     * @param int|null $expiration Cache expiration in seconds.
     * @return mixed
     */
    public function remember(string $sql, callable $callback, ?int $expiration = null)
    {
        $key        = $this->generateKey($sql);
        $expiration = $expiration ?? self::DEFAULT_EXPIRATION;

        return $this->cache->remember($key, $callback, $expiration, self::GROUP);
    }

    /**
     * Get cached result for a prepared query.
     *
     * @param string $sql SQL query template.
     * @param array $args Query arguments.
     * @param callable $callback Callback to execute query.
     * @param int|null $expiration Cache expiration in seconds.
     * @return mixed
     */
    public function rememberPrepared(string $sql, array $args, callable $callback, ?int $expiration = null)
    {
        global $wpdb;

        // Build the prepared query for the cache key
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL is parameterized by caller
        $preparedSql = $wpdb->prepare($sql, ...$args);
        $key         = $this->generateKey($preparedSql);
        $expiration  = $expiration ?? self::DEFAULT_EXPIRATION;

        return $this->cache->remember($key, $callback, $expiration, self::GROUP);
    }

    /**
     * Cache a post query result.
     *
     * @param array $args WP_Query arguments.
     * @param callable $callback Callback to execute query.
     * @param int|null $expiration Cache expiration in seconds.
     * @return mixed
     */
    public function rememberPostQuery(array $args, callable $callback, ?int $expiration = null)
    {
        $key        = 'posts_' . md5(serialize($args));
        $expiration = $expiration ?? self::DEFAULT_EXPIRATION;

        return $this->cache->remember($key, $callback, $expiration, self::GROUP);
    }

    /**
     * Cache a term query result.
     *
     * @param array $args get_terms arguments.
     * @param callable $callback Callback to execute query.
     * @param int|null $expiration Cache expiration in seconds.
     * @return mixed
     */
    public function rememberTermQuery(array $args, callable $callback, ?int $expiration = null)
    {
        $key        = 'terms_' . md5(serialize($args));
        $expiration = $expiration ?? self::DEFAULT_EXPIRATION;

        return $this->cache->remember($key, $callback, $expiration, self::GROUP);
    }

    /**
     * Cache a user query result.
     *
     * @param array $args WP_User_Query arguments.
     * @param callable $callback Callback to execute query.
     * @param int|null $expiration Cache expiration in seconds.
     * @return mixed
     */
    public function rememberUserQuery(array $args, callable $callback, ?int $expiration = null)
    {
        $key        = 'users_' . md5(serialize($args));
        $expiration = $expiration ?? self::DEFAULT_EXPIRATION;

        return $this->cache->remember($key, $callback, $expiration, self::GROUP);
    }

    /**
     * Cache a custom table query result.
     *
     * @param string $table Table name (without prefix).
     * @param array $args Query arguments.
     * @param callable $callback Callback to execute query.
     * @param int|null $expiration Cache expiration in seconds.
     * @return mixed
     */
    public function rememberTableQuery(string $table, array $args, callable $callback, ?int $expiration = null)
    {
        $key        = 'table_' . $table . '_' . md5(serialize($args));
        $expiration = $expiration ?? self::DEFAULT_EXPIRATION;

        return $this->cache->remember($key, $callback, $expiration, self::GROUP);
    }

    /**
     * Invalidate query cache.
     *
     * @param string|null $pattern Optional pattern to match keys.
     * @return bool
     */
    public function invalidate(?string $pattern = null): bool
    {
        if ($pattern === null) {
            return $this->cache->flush();
        }

        // For specific patterns, we'd need to track keys
        // For now, flush all query cache
        return $this->cache->flush();
    }

    /**
     * Invalidate post-related cache.
     *
     * @param int $postId Post ID.
     * @return void
     */
    public function invalidatePost(int $postId): void
    {
        // Clear post query cache
        // In a more advanced implementation, we'd track which queries
        // included this post and invalidate only those
        $this->cache->delete('posts_' . $postId, self::GROUP);
    }

    /**
     * Invalidate term-related cache.
     *
     * @param int $termId Term ID.
     * @return void
     */
    public function invalidateTerm(int $termId): void
    {
        $this->cache->delete('terms_' . $termId, self::GROUP);
    }

    /**
     * Invalidate table-related cache.
     *
     * @param string $table Table name.
     * @return void
     */
    public function invalidateTable(string $table): void
    {
        // Would need key tracking for precise invalidation
        // For now, this is a placeholder
    }

    /**
     * Generate a cache key from SQL.
     *
     * @param string $sql SQL query.
     * @return string
     */
    private function generateKey(string $sql): string
    {
        return 'sql_' . md5($sql);
    }

    /**
     * Register invalidation hooks.
     *
     * @return void
     */
    public function registerInvalidationHooks(): void
    {
        // Invalidate on post changes
        add_action('save_post', [$this, 'invalidatePost']);
        add_action('delete_post', [$this, 'invalidatePost']);
        add_action('trashed_post', [$this, 'invalidatePost']);

        // Invalidate on term changes
        add_action('created_term', [$this, 'invalidateTerm']);
        add_action('edited_term', [$this, 'invalidateTerm']);
        add_action('delete_term', [$this, 'invalidateTerm']);
    }
}
