<?php

namespace SiteAlerts\Cache;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CacheItem
 *
 * Represents a cache item with metadata.
 *
 * @package SiteAlerts\Cache
 * @version 1.0.0
 */
class CacheItem
{
    /**
     * Cache key
     *
     * @var string
     */
    private string $key;

    /**
     * Cached value
     *
     * @var mixed
     */
    private $value; // Mixed type

    /**
     * Whether the item was a cache hit
     *
     * @var bool
     */
    private bool $hit = false;

    /**
     * Expiration timestamp
     *
     * @var int|null
     */
    private ?int $expiration = null;

    /**
     * Tags associated with this item
     *
     * @var array
     */
    private array $tags = [];

    /**
     * CacheItem constructor.
     *
     * @param string $key Cache key.
     */
    public function __construct(string $key)
    {
        $this->key = $key;
    }

    /**
     * Create a new cache item.
     *
     * @param string $key Cache key.
     * @return self
     */
    public static function create(string $key): self
    {
        return new self($key);
    }

    /**
     * Get the cache key.
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Get the cached value.
     *
     * @return mixed
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * Set the cached value.
     *
     * @param mixed $value Value to cache.
     * @return self
     */
    public function set($value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Check if this was a cache hit.
     *
     * @return bool
     */
    public function isHit(): bool
    {
        return $this->hit;
    }

    /**
     * Mark as a cache hit.
     *
     * @param bool $hit Whether it was a hit.
     * @return self
     */
    public function setHit(bool $hit): self
    {
        $this->hit = $hit;
        return $this;
    }

    /**
     * Set expiration time.
     *
     * @param int $seconds Seconds until expiration.
     * @return self
     */
    public function expiresAfter(int $seconds): self
    {
        $this->expiration = time() + $seconds;
        return $this;
    }

    /**
     * Set expiration timestamp.
     *
     * @param int $timestamp Unix timestamp.
     * @return self
     */
    public function expiresAt(int $timestamp): self
    {
        $this->expiration = $timestamp;
        return $this;
    }

    /**
     * Get expiration timestamp.
     *
     * @return int|null
     */
    public function getExpiration(): ?int
    {
        return $this->expiration;
    }

    /**
     * Get time to live in seconds.
     *
     * @return int|null
     */
    public function getTtl(): ?int
    {
        if ($this->expiration === null) {
            return null;
        }

        $ttl = $this->expiration - time();
        return $ttl > 0 ? $ttl : 0;
    }

    /**
     * Check if the item is expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        if ($this->expiration === null) {
            return false;
        }

        return time() > $this->expiration;
    }

    /**
     * Add tags to this item.
     *
     * @param array $tags Tags to add.
     * @return self
     */
    public function tag(array $tags): self
    {
        $this->tags = array_unique(array_merge($this->tags, $tags));
        return $this;
    }

    /**
     * Get tags.
     *
     * @return array
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * Check if item has a specific tag.
     *
     * @param string $tag Tag to check.
     * @return bool
     */
    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags, true);
    }
}
