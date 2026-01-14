<?php

namespace Kodhe\Framework\Exceptions\Http;

class MethodNotAllowedException extends HttpException
{
    protected string $errorCode = 'METHOD_NOT_ALLOWED';
    protected int $httpStatusCode = 405;
    protected string $logLevel = 'info';

    public function __construct(array $allowedMethods = [])
    {
        $message = 'HTTP method not allowed';
        parent::__construct($message);
        
        if (!empty($allowedMethods)) {
            $this->withHeaders(['Allow' => implode(', ', $allowedMethods)]);
            $this->withData(['allowed_methods' => $allowedMethods]);
        }
    }

    public static function forRoute(string $route, string $method, array $allowedMethods = []): self
    {
        $message = "Method {$method} not allowed for route: {$route}";
        $exception = new self($allowedMethods);
        $exception->withData([
            'route' => $route,
            'method' => $method,
            'message' => $message
        ]);
        
        return $exception;
    }

    public static function forResource(string $resource, string $method, array $allowedMethods = []): self
    {
        $message = "Method {$method} not allowed for resource: {$resource}";
        $exception = new self($allowedMethods);
        $exception->withData([
            'resource' => $resource,
            'method' => $method,
            'message' => $message
        ]);
        
        return $exception;
    }
}