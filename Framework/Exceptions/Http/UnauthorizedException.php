<?php

namespace Kodhe\Framework\Exceptions\Http;

class UnauthorizedException extends HttpException
{
    protected string $errorCode = 'UNAUTHORIZED';
    protected int $httpStatusCode = 401;
    protected string $logLevel = 'info';

    public function __construct(string $message = 'Unauthorized access')
    {
        parent::__construct($message);
    }

    public static function invalidCredentials(string $credentialType = ''): self
    {
        $message = 'Invalid credentials';
        if ($credentialType) {
            $message .= " for {$credentialType}";
        }
        
        return new self($message);
    }

    public static function missingToken(): self
    {
        return (new self('Authentication token missing'))
            ->withData(['required' => 'authentication_token']);
    }

    public static function expiredToken(): self
    {
        return (new self('Authentication token expired'))
            ->withData(['reason' => 'token_expired']);
    }

    public static function invalidToken(): self
    {
        return (new self('Invalid authentication token'))
            ->withData(['reason' => 'token_invalid']);
    }

    public static function insufficientAuthentication(string $required = ''): self
    {
        $message = 'Insufficient authentication';
        if ($required) {
            $message .= ": {$required} required";
        }
        
        return new self($message);
    }
}