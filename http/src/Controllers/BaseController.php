<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Controllers;

use Kodhe\Framework\Http\Contracts\ControllerInterface;
use Kodhe\Framework\Http\Contracts\RequestInterface;
use Kodhe\Framework\Http\Contracts\ResponseInterface;

/**
 * Base Controller - Abstract base controller class
 * 
 * Compatible with CodeIgniter 3 while providing modern PSR-based architecture
 */
abstract class BaseController implements ControllerInterface
{
    /**
     * The application instance
     *
     * @var mixed
     */
    protected $app;

    /**
     * The request instance
     *
     * @var RequestInterface|null
     */
    protected $request;

    /**
     * The response instance
     *
     * @var ResponseInterface|null
     */
    protected $response;

    /**
     * Middleware applied to this controller
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * Create a new controller instance
     *
     * @param mixed $app
     */
    public function __construct($app = null)
    {
        $this->app = $app;
        
        if ($app) {
            $this->request = $app->get('request') ?? null;
            $this->response = $app->get('response') ?? null;
        }
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
     * Set the application instance
     *
     * @param mixed $app
     * @return $this
     */
    public function setApp($app): self
    {
        $this->app = $app;
        return $this;
    }

    /**
     * Get the request instance
     *
     * @return RequestInterface|null
     */
    public function getRequest(): ?RequestInterface
    {
        return $this->request;
    }

    /**
     * Set the request instance
     *
     * @param RequestInterface $request
     * @return $this
     */
    public function setRequest(RequestInterface $request): self
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Get the response instance
     *
     * @return ResponseInterface|null
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * Set the response instance
     *
     * @param ResponseInterface $response
     * @return $this
     */
    public function setResponse(ResponseInterface $response): self
    {
        $this->response = $response;
        return $this;
    }

    /**
     * Get a service from the container
     *
     * @param string $name
     * @return mixed
     */
    public function getService(string $name)
    {
        if ($this->app && method_exists($this->app, 'get')) {
            return $this->app->get($name);
        }

        return null;
    }

    /**
     * Check if a service exists in the container
     *
     * @param string $name
     * @return bool
     */
    public function hasService(string $name): bool
    {
        if ($this->app && method_exists($this->app, 'has')) {
            return $this->app->has($name);
        }

        return false;
    }

    /**
     * Register middleware for this controller
     *
     * @param string|array $middleware
     * @param array $options
     * @return $this
     */
    public function registerMiddleware($middleware, array $options = []): self
    {
        $middlewares = is_array($middleware) ? $middleware : [$middleware];
        
        foreach ($middlewares as $mw) {
            $this->middleware[] = [
                'middleware' => $mw,
                'options' => $options,
            ];
        }

        return $this;
    }

    /**
     * Get registered middleware
     *
     * @return array
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Call a method on the controller
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function callAction(string $method, array $parameters = [])
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException(
                "Method {$method} does not exist on controller " . get_class($this)
            );
        }

        return call_user_func_array([$this, $method], $parameters);
    }

    /**
     * Get the validated data from the request
     *
     * @param array $rules
     * @param array $messages
     * @return array
     */
    protected function validate(array $rules, array $messages = []): array
    {
        if (!$this->request) {
            return [];
        }

        // If validation service is available
        if ($this->hasService('validation')) {
            $validator = $this->getService('validation');
            
            return $validator->validate(
                $this->request->all(),
                $rules,
                $messages
            );
        }

        // Fallback: return all request data
        return $this->request->all();
    }

    /**
     * Return a JSON response
     *
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @return ResponseInterface
     */
    protected function json($data, int $status = 200, array $headers = []): ResponseInterface
    {
        if ($this->response && method_exists($this->response, 'json')) {
            return $this->response->json($data, $status, $headers);
        }

        // Fallback: create JSON response manually
        $jsonResponse = new \Kodhe\Framework\Http\Http\JsonResponse(
            $this->app,
            $data,
            $status,
            $headers
        );

        return $jsonResponse;
    }

    /**
     * Return a view response
     *
     * @param string $view
     * @param array $data
     * @return ResponseInterface
     */
    protected function view(string $view, array $data = []): ResponseInterface
    {
        if ($this->hasService('view')) {
            $viewFactory = $this->getService('view');
            $content = $viewFactory->make($view, $data)->render();
            
            return $this->response->setBody($content);
        }

        // Fallback: return basic response
        return $this->response->setBody("View: {$view}");
    }

    /**
     * Redirect to a URL
     *
     * @param string $url
     * @param int $status
     * @return ResponseInterface
     */
    protected function redirect(string $url, int $status = 302): ResponseInterface
    {
        $redirectResponse = new \Kodhe\Framework\Http\Http\RedirectResponse(
            $this->app,
            $url,
            $status
        );

        return $redirectResponse;
    }

    /**
     * Abort with an error response
     *
     * @param int $status
     * @param string $message
     * @throws \Exception
     */
    protected function abort(int $status = 400, string $message = ''): void
    {
        throw new \Kodhe\Framework\Http\Exceptions\HttpException($message ?: 'Error', $status);
    }
}
