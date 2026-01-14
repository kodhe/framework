<?php namespace Kodhe\Framework\Http\Middleware;

use Kodhe\Framework\Http\Request;
use Kodhe\Framework\Http\Response;
use Kodhe\Framework\Exceptions\BaseException;
use Throwable;

/**
 * Middleware Group
 * 
 * Digunakan untuk grouping multiple middlewares
 */
class MiddlewareGroup implements MiddlewareInterface
{
    protected $middlewares = [];
    
    public function __construct(array $middlewares = [])
    {
        $this->middlewares = $middlewares;
    }
    
    /**
     * Add middleware to group
     */
    public function add($middleware)
    {
        $this->middlewares[] = $middleware;
        log_message('debug', 'Middleware added to group: ' . $this->getMiddlewareDescription($middleware));
        return $this;
    }
    
    /**
     * Handle middleware group
     */
    public function handle(Request $request, Response $response, callable $next, array $params = [])
    {
        try {
            log_message('debug', 'MiddlewareGroup::handle() called with ' . count($this->middlewares) . ' middlewares');
            
            // Build pipeline dari middlewares dalam group
            $pipeline = $next;
            
            foreach (array_reverse($this->middlewares) as $middleware) {
                $pipeline = function($req, $res, $params) use ($middleware, $pipeline) {
                    log_message('debug', 'MiddlewareGroup executing: ' . $this->getMiddlewareDescription($middleware));
                    
                    if ($middleware instanceof MiddlewareInterface) {
                        return $middleware->handle($req, $res, $pipeline, $params);
                    }
                    
                    log_message('error', 'Invalid middleware in group');
                    return $pipeline($req, $res, $params);
                };
            }
            
            return call_user_func($pipeline, $request, $response, $params);
            
        } catch (BaseException $e) {
            log_message('error', 'MiddlewareGroup caught BaseException: ' . $e->getLogMessage());
            $e->withLogContext(array_merge($e->getLogContext(), [
                'middleware_group' => true,
                'group_middleware_count' => count($this->middlewares)
            ]));
            throw $e;
        } catch (Throwable $e) {
            log_message('error', 'MiddlewareGroup caught Throwable: ' . $e->getMessage());
            $baseException = new BaseException(
                'Middleware group execution failed: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
            $baseException
                ->withData([
                    'middleware_group' => true,
                    'middleware_count' => count($this->middlewares),
                    'request_method' => $request->method(),
                    'request_path' => $request->getUri()->getPath()
                ])
                ->withLogContext([
                    'exception_type' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ])
                ->setLogLevel('error');
            
            throw $baseException;
        }
    }
    
    /**
     * Get all middlewares in group
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }
    
    /**
     * Check if group is empty
     */
    public function isEmpty()
    {
        return empty($this->middlewares);
    }
    
    /**
     * Get middleware description
     */
    protected function getMiddlewareDescription($middleware)
    {
        if (is_object($middleware)) {
            return get_class($middleware);
        }
        
        return gettype($middleware);
    }
}