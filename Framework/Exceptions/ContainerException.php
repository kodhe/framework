<?php

namespace Kodhe\Framework\Exceptions;

class ContainerException extends ApplicationException
{
    protected string $errorCode = 'CONTAINER_ERROR';
    protected int $httpStatusCode = 500;
    protected string $logLevel = 'critical';

    public static function notFound(string $id, array $availableServices = []): self
    {
        $message = "Service not found in container: {$id}";
        $data = ['service_id' => $id];
        
        if (!empty($availableServices)) {
            $data['available_services'] = $availableServices;
        }
        
        return (new self($message))->withData($data);
    }

    public static function circularReference(string $id, array $stack = []): self
    {
        $message = "Circular reference detected for service: {$id}";
        $data = [
            'service_id' => $id,
            'dependency_stack' => $stack
        ];
        
        return (new self($message))->withData($data);
    }

    public static function serviceCreationFailed(string $id, string $reason = ''): self
    {
        $message = "Failed to create service: {$id}";
        if ($reason) {
            $message .= " - {$reason}";
        }
        
        return (new self($message))
            ->withData([
                'service_id' => $id,
                'reason' => $reason
            ]);
    }

    public static function invalidDefinition(string $id, array $definition = []): self
    {
        return (new self("Invalid service definition for: {$id}"))
            ->withData([
                'service_id' => $id,
                'definition' => $definition
            ]);
    }

    public static function notInstantiable(string $id, string $className): self
    {
        return (new self("Cannot instantiate service: {$id}"))
            ->withData([
                'service_id' => $id,
                'class' => $className
            ]);
    }
}