<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Exceptions;

/**
 * Method Not Allowed HTTP Exception - 405 error
 */
class MethodNotAllowedException extends HttpException
{
    public function __construct(
        string $message = 'Method Not Allowed',
        ?\Throwable $previous = null,
        array $headers = []
    ) {
        parent::__construct($message, 405, $previous, $headers);
    }

    public static function make(
        string $message = 'The HTTP method is not allowed for this resource.',
        array $headers = []
    ): self {
        return new static($message, null, $headers);
    }
}
