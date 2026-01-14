<?php

namespace Kodhe\Framework\Exceptions\Service;

use Kodhe\Framework\Exceptions\ApplicationException;

class ServiceException extends ApplicationException
{
    protected string $errorCode = 'SERVICE_ERROR';
    protected int $httpStatusCode = 500;
    protected string $logLevel = 'error';

    /**
     * @var string|null Service name
     */
    protected ?string $serviceName = null;

    /**
     * @var string|null Service operation
     */
    protected ?string $operation = null;

    public function __construct(string $message = '', string $serviceName = null, string $operation = null)
    {
        parent::__construct($message);
        
        $this->serviceName = $serviceName;
        $this->operation = $operation;
        
        $data = [];
        if ($serviceName) {
            $data['service'] = $serviceName;
        }
        if ($operation) {
            $data['operation'] = $operation;
        }
        
        if (!empty($data)) {
            $this->withData($data);
        }
    }

    /**
     * Get service name
     *
     * @return string|null
     */
    public function getServiceName(): ?string
    {
        return $this->serviceName;
    }

    /**
     * Get service operation
     *
     * @return string|null
     */
    public function getOperation(): ?string
    {
        return $this->operation;
    }

    public static function serviceUnavailable(string $serviceName, string $reason = ''): self
    {
        $message = "Service unavailable: {$serviceName}";
        if ($reason) {
            $message .= " - {$reason}";
        }
        
        return new self($message, $serviceName);
    }

    public static function operationFailed(string $serviceName, string $operation, string $reason = ''): self
    {
        $message = "Operation failed: {$operation} on {$serviceName}";
        if ($reason) {
            $message .= " - {$reason}";
        }
        
        return new self($message, $serviceName, $operation);
    }

    public static function invalidResponse(string $serviceName, string $operation = ''): self
    {
        $message = "Invalid response from service: {$serviceName}";
        if ($operation) {
            $message .= " ({$operation})";
        }
        
        return new self($message, $serviceName, $operation);
    }
}