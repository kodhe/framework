<?php

declare(strict_types=1);

namespace Kodhe\Framework\Exceptions\Http;

/**
 * Too Many Requests (HTTP 429)
 *
 * Thrown by rate-limiting middleware/routes when a client exceeds its
 * allowed number of requests within a decay window. Carries the
 * `Retry-After` header so downstream exception handlers can emit it
 * without recomputing the cooldown.
 */
class TooManyRequestsException extends HttpException
{
    protected string $errorCode = 'TOO_MANY_REQUESTS';
    protected int $httpStatusCode = 429;
    protected string $logLevel = 'info';

    /**
     * @var int Seconds until the client may retry
     */
    protected int $retryAfter = 60;

    public function __construct(string $message = 'Too many requests', int $retryAfter = 60)
    {
        parent::__construct($message);

        $this->setRetryAfter($retryAfter);
    }

    /**
     * Fluent factory used by routing code:
     *   throw TooManyRequestsException::create($seconds);
     *
     * @param int         $retryAfter
     * @param string|null $message
     * @return self
     */
    public static function create(int $retryAfter, ?string $message = null): self
    {
        $exception = new self(
            $message ?? ('Too many requests. Please try again in ' . max(0, $retryAfter) . ' seconds.'),
            $retryAfter
        );

        return $exception->withData([
            'retry_after' => $exception->getRetryAfter(),
        ]);
    }

    /**
     * Seconds until the client may retry (min 1, per RFC 6585)
     *
     * @param int $retryAfter
     * @return self
     */
    public function setRetryAfter(int $retryAfter): self
    {
        $this->retryAfter = max(1, $retryAfter);

        // Expose it through the standard BaseException header bag so
        // error handlers can copy getHeaders() straight onto the response.
        $this->headers = array_merge($this->headers, [
            'Retry-After' => (string) $this->retryAfter,
        ]);

        return $this;
    }

    /**
     * @return int
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
