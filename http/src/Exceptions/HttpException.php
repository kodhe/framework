<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Exceptions;

use RuntimeException;

/**
 * HTTP Exception - Base exception for HTTP errors
 * 
 * Compatible with CodeIgniter 3 while providing modern PSR-based architecture
 */
class HttpException extends RuntimeException
{
    /**
     * The HTTP status code
     *
     * @var int
     */
    protected $statusCode = 500;

    /**
     * Additional headers to send with the response
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Create a new HTTP exception instance
     *
     * @param string $message
     * @param int $statusCode
     * @param \Throwable|null $previous
     * @param array $headers
     */
    public function __construct(
        string $message = 'HTTP Error',
        int $statusCode = 500,
        ?\Throwable $previous = null,
        array $headers = []
    ) {
        parent::__construct($message, $statusCode, $previous);
        
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Get the HTTP status code
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the additional headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set a header
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Create a 400 Bad Request exception
     *
     * @param string $message
     * @param array $headers
     * @return static
     */
    public static function badRequest(
        string $message = 'Bad Request',
        array $headers = []
    ): self {
        return new static($message, 400, null, $headers);
    }

    /**
     * Create a 401 Unauthorized exception
     *
     * @param string $message
     * @param array $headers
     * @return static
     */
    public static function unauthorized(
        string $message = 'Unauthorized',
        array $headers = []
    ): self {
        return new static($message, 401, null, $headers);
    }

    /**
     * Create a 403 Forbidden exception
     *
     * @param string $message
     * @param array $headers
     * @return static
     */
    public static function forbidden(
        string $message = 'Forbidden',
        array $headers = []
    ): self {
        return new static($message, 403, null, $headers);
    }

    /**
     * Create a 404 Not Found exception
     *
     * @param string $message
     * @param array $headers
     * @return static
     */
    public static function notFound(
        string $message = 'Not Found',
        array $headers = []
    ): self {
        return new static($message, 404, null, $headers);
    }

    /**
     * Create a 405 Method Not Allowed exception
     *
     * @param string $message
     * @param array $headers
     * @return static
     */
    public static function methodNotAllowed(
        string $message = 'Method Not Allowed',
        array $headers = []
    ): self {
        return new static($message, 405, null, $headers);
    }

    /**
     * Create a 408 Request Timeout exception
     *
     * @param string $message
     * @param array $headers
     * @return static
     */
    public static function requestTimeout(
        string $message = 'Request Timeout',
        array $headers = []
    ): self {
        return new static($message, 408, null, $headers);
    }

    /**
     * Create a 409 Conflict exception
     *
     * @param string $message
     * @param array $headers
     * @return static
     */
    public static function conflict(
        string $message = 'Conflict',
        array $headers = []
    ): self {
        return new static($message, 409, null, $headers);
    }

    /**
     * Create a 422 Unprocessable Entity exception
     *
     * @param string $message
     * @param array $headers
     * @return static
     */
    public static function unprocessableEntity(
        string $message = 'Unprocessable Entity',
        array $headers = []
    ): self {
        return new static($message, 422, null, $headers);
    }

    /**
     * Create a 429 Too Many Requests exception
     *
     * @param string $message
     * @param array $headers
     * @return static
     */
    public static function tooManyRequests(
        string $message = 'Too Many Requests',
        array $headers = []
    ): self {
        return new static($message, 429, null, $headers);
    }

    /**
     * Create a 500 Internal Server Error exception
     *
     * @param string $message
     * @param array $headers
     * @return static
     */
    public static function internalServerError(
        string $message = 'Internal Server Error',
        array $headers = []
    ): self {
        return new static($message, 500, null, $headers);
    }

    /**
     * Create a 502 Bad Gateway exception
     *
     * @param string $message
     * @param array $headers
     * @return static
     */
    public static function badGateway(
        string $message = 'Bad Gateway',
        array $headers = []
    ): self {
        return new static($message, 502, null, $headers);
    }

    /**
     * Create a 503 Service Unavailable exception
     *
     * @param string $message
     * @param array $headers
     * @return static
     */
    public static function serviceUnavailable(
        string $message = 'Service Unavailable',
        array $headers = []
    ): self {
        return new static($message, 503, null, $headers);
    }

    /**
     * Create a 504 Gateway Timeout exception
     *
     * @param string $message
     * @param array $headers
     * @return static
     */
    public static function gatewayTimeout(
        string $message = 'Gateway Timeout',
        array $headers = []
    ): self {
        return new static($message, 504, null, $headers);
    }
}
