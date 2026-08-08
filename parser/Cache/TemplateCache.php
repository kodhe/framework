<?php
/**
 * Template Cache - Lazy compilation and caching
 *
 * @package CodeIgniter\Parser\Cache
 */

namespace Kodhe\Framework\Parser\Cache;

use Kodhe\Framework\Parser\Contracts\CacheInterface;

class TemplateCache implements CacheInterface
{
    /**
     * @var array
     */
    private $cache = [];

    /**
     * @var bool
     */
    private $enabled = true;

    /**
     * @var int
     */
    private static $hitCount = 0;

    /**
     * @var int
     */
    private static $missCount = 0;

    /**
     * Enable or disable cache
     *
     * @param bool $enabled
     * @return self
     */
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * Is cache enabled?
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @inheritDoc
     */
    public function get(string $key): ?string
    {
        if (!$this->enabled) {
            self::$missCount++;
            return null;
        }

        if (isset($this->cache[$key])) {
            self::$hitCount++;
            return $this->cache[$key];
        }

        self::$missCount++;
        return null;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, string $value): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $this->cache[$key] = $value;
        return true;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return $this->enabled && isset($this->cache[$key]);
    }

    /**
     * Clear all cached templates
     *
     * @return void
     */
    public function clear(): void
    {
        $this->cache = [];
    }

    /**
     * Remove specific key from cache
     *
     * @param string $key
     * @return void
     */
    public function forget(string $key): void
    {
        unset($this->cache[$key]);
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public static function getStats(): array
    {
        return [
            'hits' => self::$hitCount,
            'misses' => self::$missCount,
            'hit_rate' => (self::$hitCount + self::$missCount) > 0 
                ? self::$hitCount / (self::$hitCount + self::$missCount) 
                : 0,
        ];
    }

    /**
     * Reset statistics
     *
     * @return void
     */
    public static function resetStats(): void
    {
        self::$hitCount = 0;
        self::$missCount = 0;
    }

    /**
     * Generate cache key from template content
     *
     * @param string $template
     * @param array  $data
     * @return string
     */
    public static function generateKey(string $template, array $data = []): string
    {
        return 'tpl_' . md5($template . serialize($data));
    }
}
