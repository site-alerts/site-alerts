<?php

namespace ProactiveSiteAdvisor\Cache;

use ProactiveSiteAdvisor\Utils\DateTimeUtils;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CacheItem
 *
 * Represents a cache entry with metadata such as:
 * - value
 * - expiration
 * - hit status
 * - tags
 *
 * @package ProactiveSiteAdvisor\Cache
 * @since 1.0.0
 */
class CacheItem
{
    /**
     * Cache key.
     */
    private string $key;

    /**
     * Cached value.
     */
    private $value = null;

    /**
     * Whether this item was retrieved from cache.
     */
    private bool $hit = false;

    /**
     * Expiration timestamp.
     */
    private ?int $expiration = null;

    /**
     * Associated tags.
     */
    private array $tags = [];

    /**
     * Constructor.
     */
    public function __construct(string $key)
    {
        $this->key = $key;
    }

    /**
     * Factory method.
     */
    public static function create(string $key): self
    {
        return new self($key);
    }

    /**
     * Get key.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Get value.
     */
    public function get(): mixed
    {
        return $this->value;
    }

    /**
     * Set value.
     */
    public function set(mixed $value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Check if cache hit.
     */
    public function isHit(): bool
    {
        return $this->hit;
    }

    /**
     * Mark hit status.
     */
    public function setHit(bool $hit): self
    {
        $this->hit = $hit;
        return $this;
    }

    /**
     * Set expiration relative to now.
     */
    public function expiresAfter(?int $seconds): self
    {
        if ($seconds === null) {
            $this->expiration = null;
            return $this;
        }

        $this->expiration = DateTimeUtils::timestamp() + $seconds;

        return $this;
    }

    /**
     * Set absolute expiration timestamp.
     */
    public function expiresAt(?int $timestamp): self
    {
        $this->expiration = $timestamp;
        return $this;
    }

    /**
     * Get expiration timestamp.
     */
    public function getExpiration(): ?int
    {
        return $this->expiration;
    }

    /**
     * Get remaining TTL in seconds.
     */
    public function getTtl(): ?int
    {
        if ($this->expiration === null) {
            return null;
        }

        $ttl = $this->expiration - DateTimeUtils::timestamp();

        return $ttl > 0 ? $ttl : 0;
    }

    /**
     * Check if expired.
     */
    public function isExpired(): bool
    {
        if ($this->expiration === null) {
            return false;
        }

        return DateTimeUtils::timestamp() >= $this->expiration;
    }

    /**
     * Attach tags to item.
     */
    public function tag(array $tags): self
    {
        $normalized = array_map(
            static fn($tag) => strtolower(trim((string)$tag)),
            $tags
        );

        $this->tags = array_values(
            array_unique(array_merge($this->tags, $normalized))
        );

        return $this;
    }

    /**
     * Get tags.
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * Check if item has tag.
     */
    public function hasTag(string $tag): bool
    {
        return in_array(strtolower(trim($tag)), $this->tags, true);
    }
}