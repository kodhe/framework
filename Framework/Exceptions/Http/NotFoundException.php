<?php

namespace Kodhe\Framework\Exceptions\Http;

class NotFoundException extends HttpException
{
    protected string $errorCode = 'NOT_FOUND';
    protected int $httpStatusCode = 404;
    protected string $logLevel = 'info';

    public function __construct(string $message = 'Resource not found')
    {
        parent::__construct($message);
    }

    public static function route(string $route, string $method = ''): self
    {
        $message = "Route not found: {$route}";
        $data = ['route' => $route];
        
        if ($method) {
            $message .= " [{$method}]";
            $data['method'] = $method;
        }
        
        return (new self($message))->withData($data);
    }

    public static function resource(string $resource, $identifier = null): self
    {
        $message = "Resource not found: {$resource}";
        $data = ['resource' => $resource];
        
        if ($identifier !== null) {
            $message .= " with identifier: {$identifier}";
            $data['identifier'] = $identifier;
        }
        
        return (new self($message))->withData($data);
    }

    public static function endpoint(): self
    {
        return new self('Endpoint not found');
    }

    public static function file(string $filePath): self
    {
        return (new self("File not found: {$filePath}"))
            ->withData(['file' => $filePath])
            ->setLogLevel('warning');
    }
}