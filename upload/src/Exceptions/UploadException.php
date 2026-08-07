<?php

declare(strict_types=1);

namespace Kodhe\Upload\Exceptions;

use RuntimeException;

/**
 * Upload Exception class
 * 
 * @package Kodhe\Upload\Exceptions
 */
class UploadException extends RuntimeException
{
    /**
     * Error code
     *
     * @var string
     */
    protected $errorCode = '';

    /**
     * Create exception from upload error
     *
     * @param string $message
     * @param string $code
     * @param int $statusCode
     * @return self
     */
    public static function fromError(string $message, string $code = '', int $statusCode = 0): self
    {
        $exception = new self($message, $statusCode);
        $exception->errorCode = $code;
        return $exception;
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
}
