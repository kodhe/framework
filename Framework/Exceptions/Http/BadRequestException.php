<?php

namespace Kodhe\Framework\Exceptions\Http;

class BadRequestException extends HttpException
{
    protected string $errorCode = 'BAD_REQUEST';
    protected int $httpStatusCode = 400;
    protected string $logLevel = 'warning';

    public function __construct(string $message = 'Bad request')
    {
        parent::__construct($message);
    }

    public static function invalidParameters(array $errors = [], array $data = []): self
    {
        $exception = new self('Invalid request parameters');
        
        if (!empty($errors)) {
            $exception = $exception->withData(['validation_errors' => $errors]);
        }
        
        if (!empty($data)) {
            $existingData = $exception->getData();
            $exception = $exception->withData(array_merge($existingData, $data));
        }
        
        return $exception;
    }

    public static function invalidJson(string $jsonError = ''): self
    {
        $message = 'Invalid JSON format';
        if ($jsonError) {
            $message .= ": {$jsonError}";
        }
        
        return (new self($message))
            ->withData(['json_error' => $jsonError]);
    }

    public static function missingRequired(string $field): self
    {
        return (new self("Missing required field: {$field}"))
            ->withData(['missing_field' => $field]);
    }

    public static function malformedRequest(string $reason = ''): self
    {
        $message = 'Malformed request';
        if ($reason) {
            $message .= ": {$reason}";
        }
        
        return new self($message);
    }
}