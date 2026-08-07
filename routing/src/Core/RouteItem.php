<?php

declare(strict_types=1);

namespace Kodhe\Framework\Routing\Core;

use Kodhe\Framework\Routing\Contracts\RouteInterface;

/**
 * RouteItem - Individual route item implementation
 * 
 * PSR-12 compliant route item with full feature support.
 */
class RouteItem implements RouteInterface
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
     * @var array Route parameters
     */
    protected array $parameters = [];

    /**
     * @var array Parameter patterns
     */
    protected array $patterns = [];

    /**
     * @var string Namespace
     */
    protected string $namespace = '';

    /**
     * @var string Compiled regex pattern
     */
    protected string $compiledPattern;

    /**
     * @var array Parameter names
     */
    protected array $parameterNames = [];

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

        $this->compilePattern();
    }

    /**
     * Compile URI pattern to regex
     */
    protected function compilePattern(): void
    {
        $patterns = $this->getDefaultPatterns();

        // Extract parameter names
        preg_match_all('/\{([^}]+)\}/', $this->uri, $matches);
        $this->parameterNames = $matches[1];

        // Replace patterns with regex
        $pattern = preg_quote($this->uri, '#');
        $pattern = str_replace(['\{', '\}'], ['{', '}'], $pattern);

        foreach ($patterns as $key => $regex) {
            if (isset($this->patterns['{' . $key . '}'])) {
                $regex = $this->patterns['{' . $key . '}'];
            }
            $pattern = str_replace('{' . $key . '}', '(' . $regex . ')', $pattern);
        }

        // Replace any remaining parameters with default pattern
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);

        $this->compiledPattern = '#^' . $pattern . '$#';
    }

    /**
     * Get default parameter patterns
     */
    protected function getDefaultPatterns(): array
    {
        return [
            'id' => '([0-9]+)',
            'slug' => '([a-z0-9-]+)',
            'uuid' => '([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})',
            'any' => '(.+)',
            'string' => '([a-zA-Z]+)',
            'alpha' => '([a-zA-Z]+)',
            'num' => '([0-9]+)',
            'alnum' => '([a-zA-Z0-9]+)',
        ];
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
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function matches(string $uri): bool
    {
        $uri = '/' . trim($uri, '/');
        if ($uri === '') {
            $uri = '/';
        }

        if (preg_match($this->compiledPattern, $uri, $matches)) {
            array_shift($matches);

            if (count($matches) === count($this->parameterNames)) {
                $this->parameters = array_combine($this->parameterNames, $matches);
            } else {
                $this->parameters = $matches;
            }

            return true;
        }

        return false;
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
     * Add parameter pattern
     */
    public function where(string $parameter, string $pattern): self
    {
        $this->patterns['{' . $parameter . '}'] = $pattern;
        $this->compilePattern();
        return $this;
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
