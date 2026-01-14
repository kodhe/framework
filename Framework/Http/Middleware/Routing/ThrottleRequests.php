<?php namespace Kodhe\Framework\Http\Middleware\Routing;

use Kodhe\Framework\Http\Request;
use Kodhe\Framework\Http\Response;
use Kodhe\Framework\Exceptions\TooManyRequestsException;
use Kodhe\Framework\Routing\RateLimiter;

class ThrottleRequests
{
    /**
     * @var RateLimiter Rate limiter instance
     */
    protected $limiter;
    
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }
    
    public function handle(Request $request, \Closure $next, int $maxAttempts = 60, int $decayMinutes = 1, string $key = null)
    {
        $route = $request->getAttribute('route');
        
        // Generate rate limit key
        $limitKey = $this->resolveRequestSignature($request, $route, $key);
        
        // Check rate limit
        if ($this->limiter->tooManyAttempts($limitKey, $maxAttempts)) {
            $retryAfter = $this->limiter->availableIn($limitKey);
            
            throw new TooManyRequestsException(
                'Too many requests. Please try again in ' . $retryAfter . ' seconds.',
                $retryAfter
            );
        }
        
        // Increment attempts
        $this->limiter->hit($limitKey, $decayMinutes * 60);
        
        // Get response
        $response = $next($request);
        
        // Add rate limit headers
        if ($response instanceof Response) {
            $headers = $this->limiter->getHeaders($limitKey, $maxAttempts);
            
            foreach ($headers as $name => $value) {
                $response->setHeader($name, $value);
            }
        }
        
        return $response;
    }
    
    /**
     * Resolve request signature for rate limiting
     */
    protected function resolveRequestSignature(Request $request, $route = null, string $customKey = null): string
    {
        if ($customKey) {
            return $customKey;
        }
        
        if ($route && method_exists($route, 'getRouteKey')) {
            $routeKey = $route->getRouteKey();
            $identifier = $this->getRequestIdentifier($request);
            
            return $this->limiter->forRoute($routeKey, $identifier);
        }
        
        // Fallback: IP-based rate limiting
        return 'ip:' . $request->ip();
    }
    
    /**
     * Get identifier for request
     */
    protected function getRequestIdentifier(Request $request): string
    {
        // Try user ID first
        if ($user = $request->user()) {
            return 'user:' . $user->id;
        }
        
        // Try session
        if (session_id()) {
            return 'session:' . session_id();
        }
        
        // Fallback to IP
        return 'ip:' . $request->ip();
    }
}