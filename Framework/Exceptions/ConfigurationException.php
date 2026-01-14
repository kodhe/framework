<?php

namespace Kodhe\Framework\Exceptions;

class ConfigurationException extends ApplicationException
{
    protected string $errorCode = 'CONFIGURATION_ERROR';
    protected int $httpStatusCode = 500;
    protected string $logLevel = 'critical';

    public static function missingConfig(string $configKey, array $availableKeys = []): self
    {
        $message = "Missing configuration: {$configKey}";
        $data = ['key' => $configKey];
        
        if (!empty($availableKeys)) {
            $data['available_keys'] = $availableKeys;
        }
        
        return (new self($message))->withData($data);
    }

    public static function invalidConfig(string $configKey, $value, string $reason = ''): self
    {
        $message = "Invalid configuration for: {$configKey}";
        if ($reason) {
            $message .= " - {$reason}";
        }
        
        return (new self($message))
            ->withData([
                'key' => $configKey,
                'value' => $value,
                'reason' => $reason
            ]);
    }

    public static function fileNotFound(string $filePath): self
    {
        return (new self("Configuration file not found: {$filePath}"))
            ->withData(['file' => $filePath]);
    }

    public static function parseError(string $filePath, string $error): self
    {
        return (new self("Failed to parse configuration file: {$filePath}"))
            ->withData([
                'file' => $filePath,
                'parse_error' => $error
            ]);
    }

    public static function validationFailed(array $errors): self
    {
        return (new self("Configuration validation failed"))
            ->withData(['validation_errors' => $errors])
            ->setLogLevel('error');
    }
}