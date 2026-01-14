<?php

namespace Kodhe\Framework\Exceptions;

use Throwable;
use JsonSerializable;

class BaseException extends \Exception implements JsonSerializable
{
    /**
     * @var array Additional error data
     */
    protected array $data = [];

    /**
     * @var string Error code for API responses
     */
    protected string $errorCode = 'INTERNAL_ERROR';

    /**
     * @var int HTTP status code
     */
    protected int $httpStatusCode = 500;

    /**
     * @var array Headers to include in HTTP response
     */
    protected array $headers = [];

    /**
     * @var string Log level for this exception
     */
    protected string $logLevel = 'error';

    /**
     * @var bool Whether to log this exception
     */
    protected bool $shouldLog = true;

    /**
     * @var array Additional context for logging
     */
    protected array $logContext = [];

    /**
     * BaseException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Set additional error data
     *
     * @param array $data
     * @return self
     */
    public function withData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get additional error data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set error code
     *
     * @param string $errorCode
     * @return self
     */
    public function setErrorCode(string $errorCode): self
    {
        $this->errorCode = $errorCode;
        return $this;
    }

    /**
     * Get error code
     *
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Set HTTP status code
     *
     * @param int $httpStatusCode
     * @return self
     */
    public function setHttpStatusCode(int $httpStatusCode): self
    {
        $this->httpStatusCode = $httpStatusCode;
        return $this;
    }

    /**
     * Get HTTP status code
     *
     * @return int
     */
    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    /**
     * Set HTTP headers
     *
     * @param array $headers
     * @return self
     */
    public function withHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * Get HTTP headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set log level
     *
     * @param string $logLevel
     * @return self
     */
    public function setLogLevel(string $logLevel): self
    {
        $this->logLevel = $logLevel;
        return $this;
    }

    /**
     * Get log level
     *
     * @return string
     */
    public function getLogLevel(): string
    {
        return $this->logLevel;
    }

    /**
     * Set whether to log this exception
     *
     * @param bool $shouldLog
     * @return self
     */
    public function setShouldLog(bool $shouldLog): self
    {
        $this->shouldLog = $shouldLog;
        return $this;
    }

    /**
     * Get whether to log this exception
     *
     * @return bool
     */
    public function shouldLog(): bool
    {
        return $this->shouldLog;
    }

    /**
     * Set log context
     *
     * @param array $context
     * @return self
     */
    public function withLogContext(array $context): self
    {
        $this->logContext = $context;
        return $this;
    }

    /**
     * Get log context
     *
     * @return array
     */
    public function getLogContext(): array
    {
        return $this->logContext;
    }

    /**
     * Create log message
     *
     * @return string
     */
    public function getLogMessage(): string
    {
        $message = sprintf(
            '[%s] %s: %s in %s:%s',
            $this->errorCode,
            get_class($this),
            $this->getMessage(),
            $this->getFile(),
            $this->getLine()
        );

        if (!empty($this->logContext)) {
            $message .= ' | Context: ' . json_encode($this->logContext);
        }

        return $message;
    }

    /**
     * Convert exception to array
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = [
            'error' => [
                'code' => $this->errorCode,
                'message' => $this->getMessage(),
                'status' => $this->httpStatusCode,
            ]
        ];

        if (!empty($this->data)) {
            $data['error']['data'] = $this->data;
        }

        return $data;
    }

    /**
     * Convert exception to JSON
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    /**
     * JsonSerializable implementation
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * String representation
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getLogMessage();
    }
}