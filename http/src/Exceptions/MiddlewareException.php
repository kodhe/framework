<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Exceptions;

/**
 * Middleware Exception - Error in middleware execution
 */
class MiddlewareException extends HttpException
{
    /**
     * The middleware class that caused the exception
     *
     * @var string|null
     */
    protected $middlewareClass;

    public function __construct(
        string $message = 'Middleware Error',
        ?string $middlewareClass = null,
        int $statusCode = 500,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
        
        $this->middlewareClass = $middlewareClass;
    }

    /**
     * Get the middleware class
     *
     * @return string|null
     */
    public function getMiddlewareClass(): ?string
    {
        return $this->middlewareClass;
    }

    public static function make(
        string $message = 'An error occurred while processing middleware.',
        ?string $middlewareClass = null,
        int $statusCode = 500
    ): self {
        return new static($message, $middlewareClass, $statusCode);
    }
}
