<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Exceptions;

/**
 * Not Found HTTP Exception - 404 error
 */
class NotFoundHttpException extends HttpException
{
    public function __construct(
        string $message = 'Not Found',
        ?\Throwable $previous = null,
        array $headers = []
    ) {
        parent::__construct($message, 404, $previous, $headers);
    }

    public static function make(
        string $message = 'The requested resource was not found.',
        array $headers = []
    ): self {
        return new static($message, null, $headers);
    }
}
