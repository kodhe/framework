<?php namespace Kodhe\Framework\Http\Kernel;

use Kodhe\Framework\Container\Container;
use Kodhe\Framework\Http\Request;
use Kodhe\Framework\Http\Response;
use Kodhe\Framework\Http\Middleware\MiddlewareRegistry;
use Kodhe\Framework\Http\Middleware\MiddlewareInterface;

/**
 * Middleware Pipeline Manager
 * 
 * Handles middleware pipeline execution for the application kernel
 */
class MiddlewarePipelineManager
{
    /**
     * @var Container
     */
    protected $container;
    
    /**
     * @var string
     */
    protected $configPath;
    
    /**
     * Constructor
     * 
     * @param Container $container
     * @param string $configPath
     */
    public function __construct(Container $container, string $configPath = '')
    {
        $this->container = $container;
        $this->configPath = $configPath;
    }
    
    /**
     * Run middleware pipeline for modern routing
     * 
     * @param mixed $servicemanager
     * @param array $routing
     * @return Response|null
     */
    public function run($servicemanager, array $routing): ?Response
    {
        // Only run middleware for modern routing
        if (!isset($routing['type']) || $routing['type'] !== 'modern') {
            log_message('debug', 'Skipping middleware for non-modern routing');
            return null;
        }
        
        log_message('debug', '=== START MIDDLEWARE PIPELINE ===');
        
        // Get request and response from container
        $request = $this->container->make('Request');
        $response = $this->container->make('Response');
        
        try {
            // Create pipeline
            $pipeline = new Pipeline($request, $response);
            
            // Add global middlewares
            $this->addGlobalMiddlewares($pipeline);
            
            // Add route-specific middlewares if any
            if (isset($routing['middleware']) && !empty($routing['middleware'])) {
                log_message('debug', 'Route has middleware: ' . print_r($routing['middleware'], true));
                $this->addRouteMiddlewares($pipeline, $routing['middleware']);
            } else {
                log_message('debug', 'Route has no middleware');
            }
            
            // Set handler
            $pipeline->setHandler(function ($request, $response, $params) use ($routing, $servicemanager) {
                log_message('debug', '=== MIDDLEWARE HANDLER CALLED ===');
                
                // Update response in servicemanager
                $servicemanager->setResponse($response);
                
                // Execute controller
                $controllerExecutor = $this->container->make('ControllerExecutor');
                $controllerExecutor->execute($routing);
                
                // Return response from servicemanager
                return $servicemanager->getResponse();
            });
            
            // Run pipeline
            $pipelineResponse = $pipeline->run($routing['segments']);
            
            if ($pipelineResponse instanceof Response) {
                log_message('debug', '=== PIPELINE RETURNED RESPONSE ===');
                
                // Update response in container
                $this->updateContainerResponse($pipelineResponse);
                
                // Update in servicemanager
                $servicemanager->setResponse($pipelineResponse);
                
                return $pipelineResponse;
            }
            
            log_message('debug', '=== PIPELINE RETURNED NULL ===');
            return null;
            
        } catch (\Exception $e) {
            log_message('error', 'Middleware pipeline error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            // Fallback
            log_message('debug', 'Falling back to direct controller execution');
            return null;
        }
    }
    
    /**
     * Execute single middleware
     * 
     * @param mixed $middleware
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return mixed
     */
    public function executeMiddleware($middleware, Request $request, Response $response, callable $next)
    {
        $registry = new MiddlewareRegistry();
        $resolved = $registry->resolve($middleware);
        
        if ($resolved instanceof MiddlewareInterface) {
            log_message('debug', 'Executing middleware: ' . get_class($resolved));
            return $resolved->handle($request, $response, $next, []);
        }
        
        log_message('error', 'Cannot execute middleware: ' . print_r($middleware, true));
        return $next($request, $response);
    }
    
    /**
     * Add route-specific middlewares
     * 
     * @param Pipeline $pipeline
     * @param mixed $middlewares
     * @return void
     */
    protected function addRouteMiddlewares(Pipeline $pipeline, $middlewares): void
    {
        // Normalize input
        if (is_string($middlewares)) {
            // Can be single middleware or comma-separated list
            if (strpos($middlewares, ',') !== false) {
                $middlewares = array_map('trim', explode(',', $middlewares));
            } else {
                $middlewares = [$middlewares];
            }
        }
        
        if (!is_array($middlewares)) {
            log_message('error', 'Route middlewares must be string or array');
            return;
        }
        
        log_message('debug', 'Adding ' . count($middlewares) . ' route middlewares');
        
        foreach ($middlewares as $index => $middleware) {
            log_message('debug', "Route middleware [{$index}]: " . 
                (is_string($middleware) ? $middleware : gettype($middleware)));
            
            // If middleware is array, pipe as inline group
            if (is_array($middleware)) {
                log_message('debug', "Piping array as inline group with " . count($middleware) . " items");
                foreach ($middleware as $mw) {
                    $pipeline->pipe($mw);
                }
            } else {
                $pipeline->pipe($middleware);
            }
        }
    }
    
    /**
     * Add global middlewares
     * 
     * @param Pipeline $pipeline
     * @return void
     */
    protected function addGlobalMiddlewares(Pipeline $pipeline): void
    {
        // Load global middlewares from config
        $configPath = $this->getConfigPath() . 'middleware.php';
        
        log_message('debug', 'Looking for middleware config at: ' . $configPath);
        
        if (file_exists($configPath)) {
            $config = require $configPath;
            log_message('debug', 'Middleware config loaded successfully');
            
            if (isset($config['global']) && is_array($config['global'])) {
                log_message('debug', 'Found ' . count($config['global']) . ' global middlewares');
                
                foreach ($config['global'] as $index => $middleware) {
                    log_message('debug', "Adding global middleware [{$index}]: " . $middleware);
                    $pipeline->pipe($middleware);
                }
            } else {
                log_message('debug', 'No global middlewares found in config');
            }
            
            // Log groups and aliases for debugging
            if (isset($config['aliases'])) {
                log_message('debug', 'Available aliases: ' . implode(', ', array_keys($config['aliases'])));
            }
            
            if (isset($config['groups'])) {
                log_message('debug', 'Available groups: ' . implode(', ', array_keys($config['groups'])));
            }
            
        } else {
            log_message('warning', 'Middleware config file not found at: ' . $configPath);
        }
    }
    
    /**
     * Update response in container
     * 
     * @param Response $response
     * @return void
     */
    protected function updateContainerResponse(Response $response): void
    {
        if (method_exists($this->container, 'set')) {
            $this->container->set('Response', $response);
        } elseif (method_exists($this->container, 'replace')) {
            $this->container->replace('Response', $response);
        } else {
            // Fallback: directly assign to registry
            $this->container->register('Response', $response);
        }
    }
    
    /**
     * Get config path
     * 
     * @return string
     */
    protected function getConfigPath(): string
    {
        if (!empty($this->configPath)) {
            return $this->configPath;
        }
        
        return resolve_path(APPPATH, 'config') . DIRECTORY_SEPARATOR;
    }
}
