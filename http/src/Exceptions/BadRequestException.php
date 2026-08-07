<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Exceptions;

/**
 * Bad Request HTTP Exception - 400 error
 */
class BadRequestException extends HttpException
{
    public function __construct(
        string $message = 'Bad Request',
        ?\Throwable $previous = null,
        array $headers = []
    ) {
        parent::__construct($message, 400, $previous, $headers);
    }

    public static function make(
        string $message = 'The request was malformed or contained invalid parameters.',
        array $headers = []
    ): self {
        return new static($message, null, $headers);
    }
}
