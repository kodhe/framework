<?php

declare(strict_types=1);

namespace Kodhe\Parser\Cache;

use Kodhe\Parser\Contracts\CacheInterface;

/**
 * Template Cache
 *
 * Provides caching for compiled templates with lazy compilation support.
 */
class TemplateCache implements CacheInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $cache = [];

    /**
     * @var array<string, int>
     */
    private array $expiry = [];

    private bool $enabled = true;

    public function __construct(bool $enabled = true)
    {
        $this->enabled = $enabled;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function get(string $key): mixed
    {
        if (!$this->enabled) {
            return null;
        }

        if ($this->has($key)) {
            return $this->cache[$key];
        }

        return null;
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $this->cache[$key] = $value;
        $this->expiry[$key] = time() + $ttl;

        return true;
    }

    public function has(string $key): bool
    {
        if (!isset($this->cache[$key])) {
            return false;
        }

        if (isset($this->expiry[$key]) && time() > $this->expiry[$key]) {
            unset($this->cache[$key], $this->expiry[$key]);
            return false;
        }

        return true;
    }

    public function remove(string $key): bool
    {
        if (isset($this->cache[$key])) {
            unset($this->cache[$key], $this->expiry[$key]);
            return true;
        }

        return false;
    }

    /**
     * Clear all cached items
     */
    public function clear(): void
    {
        $this->cache = [];
        $this->expiry = [];
    }

    /**
     * Get or compute value with lazy evaluation
     *
     * @template T
     * @param string $key Cache key
     * @param callable(): T $compute Function to compute value if not cached
     * @param int $ttl Time to live in seconds
     * @return T
     */
    public function getOrCompute(string $key, callable $compute, int $ttl = 3600): mixed
    {
        $cached = $this->get($key);
        if ($cached !== null) {
            return $cached;
        }

        $value = $compute();
        $this->set($key, $value, $ttl);

        return $value;
    }
}
