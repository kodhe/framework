<?php namespace Kodhe\Framework\Routing;

use Kodhe\Framework\Cache\CacheInterface;

class RateLimiter
{
    /**
     * @var CacheInterface Cache instance
     */
    protected $cache;
    
    /**
     * @var array Rate limit hits
     */
    protected $hits = [];
    
    /**
     * @var array Default configurations
     */
    protected $defaults = [
        'max_attempts' => 60,
        'decay_minutes' => 1,
        'prefix' => 'rate_limit:',
    ];
    
    public function __construct(CacheInterface $cache, array $config = [])
    {
        $this->cache = $cache;
        $this->defaults = array_merge($this->defaults, $config);
    }
    
    /**
     * Determine if too many attempts
     */
    public function tooManyAttempts(string $key, int $maxAttempts = null): bool
    {
        $maxAttempts = $maxAttempts ?? $this->defaults['max_attempts'];
        $attempts = $this->attempts($key);
        
        return $attempts >= $maxAttempts;
    }
    
    /**
     * Get number of attempts
     */
    public function attempts(string $key): int
    {
        $cacheKey = $this->defaults['prefix'] . $key . ':attempts';
        return (int) $this->cache->get($cacheKey, 0);
    }
    
    /**
     * Increment attempts
     */
    public function hit(string $key, int $decaySeconds = null): int
    {
        $decaySeconds = $decaySeconds ?? ($this->defaults['decay_minutes'] * 60);
        $cacheKey = $this->defaults['prefix'] . $key . ':attempts';
        
        $attempts = $this->attempts($key) + 1;
        $this->cache->set($cacheKey, $attempts, $decaySeconds);
        
        // Store reset time
        $resetKey = $this->defaults['prefix'] . $key . ':reset';
        $this->cache->set($resetKey, time() + $decaySeconds, $decaySeconds);
        
        $this->hits[$key] = $attempts;
        
        return $attempts;
    }
    
    /**
     * Get available time in seconds
     */
    public function availableIn(string $key): int
    {
        $resetKey = $this->defaults['prefix'] . $key . ':reset';
        $resetTime = (int) $this->cache->get($resetKey, 0);
        
        return max(0, $resetTime - time());
    }
    
    /**
     * Get remaining attempts
     */
    public function remaining(string $key, int $maxAttempts = null): int
    {
        $maxAttempts = $maxAttempts ?? $this->defaults['max_attempts'];
        $attempts = $this->attempts($key);
        
        return max(0, $maxAttempts - $attempts);
    }
    
    /**
     * Get limit headers
     */
    public function getHeaders(string $key, int $maxAttempts = null): array
    {
        $maxAttempts = $maxAttempts ?? $this->defaults['max_attempts'];
        $remaining = $this->remaining($key, $maxAttempts);
        $resetTime = time() + $this->availableIn($key);
        
        return [
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remaining,
            'X-RateLimit-Reset' => $resetTime,
        ];
    }
    
    /**
     * Reset attempts for a key
     */
    public function reset(string $key): void
    {
        $cacheKey = $this->defaults['prefix'] . $key . ':attempts';
        $resetKey = $this->defaults['prefix'] . $key . ':reset';
        
        $this->cache->delete($cacheKey);
        $this->cache->delete($resetKey);
        
        unset($this->hits[$key]);
    }
    
    /**
     * Clear all rate limits
     */
    public function clear(): void
    {
        foreach (array_keys($this->hits) as $key) {
            $this->reset($key);
        }
        $this->hits = [];
    }
    
    /**
     * Create rate limiter for route
     */
    public function forRoute(string $routeKey, string $identifier): string
    {
        return $routeKey . ':' . $identifier;
    }
}