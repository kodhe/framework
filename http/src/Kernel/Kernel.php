<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Kernel;

use Kodhe\Framework\Http\Contracts\KernelInterface;
use Kodhe\Framework\Http\Contracts\RequestInterface;
use Kodhe\Framework\Http\Contracts\ResponseInterface;
use Kodhe\Framework\Http\Contracts\MiddlewareRegistryInterface;
use Kodhe\Framework\Http\Contracts\PipelineInterface;
use Kodhe\Framework\Http\Middleware\MiddlewareRegistry;
use Kodhe\Framework\Http\Middleware\MiddlewareStack;
use Kodhe\Framework\Http\Exceptions\HttpException;
use Exception;

/**
 * HTTP Kernel - Application entry point for handling HTTP requests
 * 
 * Compatible with CodeIgniter 3 while providing modern PSR-based architecture
 */
class Kernel implements KernelInterface
{
    /**
     * The application instance
     *
     * @var mixed
     */
    protected $app;

    /**
     * The middleware registry
     *
     * @var MiddlewareRegistryInterface
     */
    protected $middlewareRegistry;

    /**
     * The pipeline instance
     *
     * @var PipelineInterface|null
     */
    protected $pipeline;

    /**
     * The bootstrap classes
     *
     * @var array
     */
    protected $bootstrappers = [];

    /**
     * Global middleware stack
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * Route middleware groups
     *
     * @var array
     */
    protected $middlewareGroups = [];

    /**
     * Priority sorted middleware cache
     *
     * @var array|null
     */
    protected $sortedMiddlewareCache = null;

    /**
     * Create a new HTTP kernel instance
     *
     * @param mixed $app
     * @param MiddlewareRegistryInterface|null $middlewareRegistry
     */
    public function __construct($app, ?MiddlewareRegistryInterface $middlewareRegistry = null)
    {
        $this->app = $app;
        $this->middlewareRegistry = $middlewareRegistry ?? new MiddlewareRegistry();
        
        $this->configure();
    }

    /**
     * Configure the kernel
     *
     * @return void
     */
    protected function configure(): void
    {
        // Can be overridden by child classes
    }

    /**
     * Handle an incoming HTTP request
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(RequestInterface $request): ResponseInterface
    {
        try {
            $this->bootstrap();

            return $this->sendRequestThroughRouter($request);
        } catch (Exception $e) {
            return $this->reportException($e);
        }
    }

    /**
     * Bootstrap the application
     *
     * @return void
     */
    protected function bootstrap(): void
    {
        foreach ($this->bootstrappers as $bootstrapper) {
            $this->app->make($bootstrapper)->bootstrap($this->app);
        }
    }

    /**
     * Send the given request through the middleware / router
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    protected function sendRequestThroughRouter(RequestInterface $request): ResponseInterface
    {
        $this->pipeline = $this->getPipeline();

        return $this->pipeline->send($request)
            ->through($this->calculateMiddlewareClasses($request))
            ->then(function ($request) {
                return $this->dispatchToRouter($request);
            });
    }

    /**
     * Dispatch the request to the router
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    protected function dispatchToRouter(RequestInterface $request): ResponseInterface
    {
        // This will be handled by the routing component
        // For now, return a basic response
        return $this->app->get('router')->dispatch($request);
    }

    /**
     * Get the pipeline instance
     *
     * @return PipelineInterface
     */
    protected function getPipeline(): PipelineInterface
    {
        if (!$this->pipeline) {
            $this->pipeline = new Pipeline($this->app);
        }

        return $this->pipeline;
    }

    /**
     * Calculate the middleware classes for the given request
     *
     * @param RequestInterface $request
     * @return array
     */
    protected function calculateMiddlewareClasses(RequestInterface $request): array
    {
        if ($this->sortedMiddlewareCache !== null) {
            return $this->sortedMiddlewareCache;
        }

        $middleware = $this->middleware;

        // Add any route-specific middleware here
        
        $this->sortedMiddlewareCache = $middleware;

        return $middleware;
    }

    /**
     * Report the exception to the logger
     *
     * @param Exception $e
     * @return ResponseInterface
     */
    protected function reportException(Exception $e): ResponseInterface
    {
        // Log the exception
        if ($this->app->has('log')) {
            $this->app->get('log')->error($e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        // Return error response
        return $this->renderException($e);
    }

    /**
     * Render the exception into a response
     *
     * @param Exception $e
     * @return ResponseInterface
     */
    protected function renderException(Exception $e): ResponseInterface
    {
        $status = 500;
        
        if ($e instanceof HttpException) {
            $status = $e->getStatusCode();
        }

        $response = $this->app->get('response');
        
        return $response->setStatusCode($status)
            ->setBody($this->convertExceptionToContent($e));
    }

    /**
     * Convert the given exception to content
     *
     * @param Exception $e
     * @return string
     */
    protected function convertExceptionToContent(Exception $e): string
    {
        if ($this->app->config->item('environment') === 'development') {
            return sprintf(
                "<h1>%s</h1><p>%s</p><pre>%s</pre>",
                htmlspecialchars(get_class($e)),
                htmlspecialchars($e->getMessage()),
                htmlspecialchars($e->getTraceAsString())
            );
        }

        return '<h1>Server Error</h1><p>An unexpected error occurred.</p>';
    }

    /**
     * Get the middleware registry
     *
     * @return MiddlewareRegistryInterface
     */
    public function getMiddlewareRegistry(): MiddlewareRegistryInterface
    {
        return $this->middlewareRegistry;
    }

    /**
     * Register middleware
     *
     * @param array|string $middleware
     * @return $this
     */
    public function pushMiddleware($middleware): self
    {
        $middlewares = is_array($middleware) ? $middleware : [$middleware];
        
        foreach ($middlewares as $mw) {
            $this->middleware[] = $mw;
        }

        $this->sortedMiddlewareCache = null;

        return $this;
    }

    /**
     * Set the global middleware
     *
     * @param array $middleware
     * @return $this
     */
    public function setMiddleware(array $middleware): self
    {
        $this->middleware = $middleware;
        $this->sortedMiddlewareCache = null;

        return $this;
    }

    /**
     * Get the application instance
     *
     * @return mixed
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Terminate the kernel
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return void
     */
    public function terminate(RequestInterface $request, ResponseInterface $response): void
    {
        // Call terminate on terminable middleware
        foreach ($this->middleware as $middleware) {
            $instance = $this->resolveMiddleware($middleware);
            
            if (method_exists($instance, 'terminate')) {
                $instance->terminate($request, $response);
            }
        }
    }

    /**
     * Resolve a middleware instance
     *
     * @param string|array $middleware
     * @return object
     */
    protected function resolveMiddleware($middleware)
    {
        if (is_object($middleware)) {
            return $middleware;
        }

        if (is_array($middleware)) {
            return $this->app->make($middleware[0], $middleware[1] ?? []);
        }

        return $this->app->make($middleware);
    }
}
