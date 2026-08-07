<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Routing\Core;

use Kodhe\Framework\Http\Routing\Contracts\RouteInterface;

/**
 * Route - Core route implementation
 * 
 * PSR-12 compliant route class implementing RouteInterface.
 */
class Route implements RouteInterface
{
    /**
     * @var string HTTP method
     */
    protected string $method;

    /**
     * @var string URI pattern
     */
    protected string $uri;

    /**
     * @var mixed Route action
     */
    protected $action;

    /**
     * @var array Middleware
     */
    protected array $middleware = [];

    /**
     * @var string|null Route name
     */
    protected ?string $name = null;

    /**
     * @var string Namespace
     */
    protected string $namespace = '';

    /**
     * Constructor
     */
    public function __construct(
        string $method,
        string $uri,
        $action,
        array $middleware = [],
        string $namespace = ''
    ) {
        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->action = $action;
        $this->middleware = $middleware;
        $this->namespace = $namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * {@inheritdoc}
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * {@inheritdoc}
     */
    public function middleware($middleware): self
    {
        $middlewares = is_array($middleware) ? $middleware : func_get_args();
        $this->middleware = array_merge($this->middleware, $middlewares);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function matches(string $uri): bool
    {
        return $this->uri === $uri;
    }

    /**
     * {@inheritdoc}
     */
    public function url(array $parameters = []): string
    {
        $url = $this->uri;
        
        foreach ($parameters as $key => $value) {
            $url = str_replace('{' . $key . '}', (string)$value, $url);
        }
        
        // Remove optional parameters
        $url = preg_replace('/\{[^}]+\}\/?/', '', $url);
        
        return '/' . trim($url, '/');
    }

    /**
     * Set namespace
     */
    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;
        return $this;
    }
}
