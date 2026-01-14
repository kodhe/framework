<?php

namespace Kodhe\Framework\Exceptions\Service;

class ExternalServiceException extends ServiceException
{
    protected string $errorCode = 'EXTERNAL_SERVICE_ERROR';
    protected int $httpStatusCode = 502; // Bad Gateway
    protected string $logLevel = 'error';

    /**
     * @var int|null HTTP status code from external service
     */
    protected ?int $externalStatusCode = null;

    /**
     * @var array|null Response from external service
     */
    protected ?array $externalResponse = null;

    /**
     * @var string|null External service endpoint
     */
    protected ?string $endpoint = null;

    public function __construct(string $serviceName, string $message = '', int $code = 0)
    {
        $fullMessage = "External service error";
        if ($serviceName) {
            $fullMessage .= " ({$serviceName})";
        }
        if ($message) {
            $fullMessage .= ": {$message}";
        }
        
        parent::__construct($fullMessage, $serviceName);
    }

    /**
     * Set external status code
     *
     * @param int|null $statusCode
     * @return self
     */
    public function withExternalStatusCode(?int $statusCode): self
    {
        $this->externalStatusCode = $statusCode;
        $this->withData(['external_status' => $statusCode]);
        return $this;
    }

    /**
     * Get external status code
     *
     * @return int|null
     */
    public function getExternalStatusCode(): ?int
    {
        return $this->externalStatusCode;
    }

    /**
     * Set external response
     *
     * @param array|null $response
     * @return self
     */
    public function withExternalResponse(?array $response): self
    {
        $this->externalResponse = $response;
        $this->withData(['external_response' => $response]);
        return $this;
    }

    /**
     * Get external response
     *
     * @return array|null
     */
    public function getExternalResponse(): ?array
    {
        return $this->externalResponse;
    }

    /**
     * Set endpoint
     *
     * @param string|null $endpoint
     * @return self
     */
    public function withEndpoint(?string $endpoint): self
    {
        $this->endpoint = $endpoint;
        $this->withData(['endpoint' => $endpoint]);
        return $this;
    }

    /**
     * Get endpoint
     *
     * @return string|null
     */
    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    public static function timeout(string $serviceName, string $endpoint = ''): self
    {
        $exception = new self($serviceName, 'Request timeout');
        if ($endpoint) {
            $exception->withEndpoint($endpoint);
        }
        return $exception;
    }

    public static function connectionFailed(string $serviceName, string $endpoint = ''): self
    {
        $exception = new self($serviceName, 'Connection failed');
        if ($endpoint) {
            $exception->withEndpoint($endpoint);
        }
        return $exception;
    }

    public static function invalidResponseFormat(string $serviceName, string $endpoint = ''): self
    {
        $exception = new self($serviceName, 'Invalid response format');
        if ($endpoint) {
            $exception->withEndpoint($endpoint);
        }
        return $exception;
    }

    public static function fromHttpResponse(string $serviceName, int $statusCode, array $response = []): self
    {
        $exception = new self($serviceName, "HTTP {$statusCode} from external service");
        return $exception
            ->withExternalStatusCode($statusCode)
            ->withExternalResponse($response);
    }
}