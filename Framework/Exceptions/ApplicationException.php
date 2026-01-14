<?php

namespace Kodhe\Framework\Exceptions;

class ApplicationException extends BaseException
{
    protected string $errorCode = 'APPLICATION_ERROR';
    protected int $httpStatusCode = 500;
    protected string $logLevel = 'error';

    public static function generic(string $message, array $context = []): self
    {
        $exception = new self($message);
        return $exception->withLogContext($context);
    }

    public static function runtimeError(string $message, Throwable $previous = null): self
    {
        return new self($message, 0, $previous);
    }
}