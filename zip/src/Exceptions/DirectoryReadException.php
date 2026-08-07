<?php

declare(strict_types=1);

namespace Kodhe\Zip\Exceptions;

/**
 * Exception thrown when directory operations fail
 */
class DirectoryReadException extends ZipException
{
    public static function create(string $path, ?\Throwable $previous = null): self
    {
        return new self(
            sprintf('Failed to read directory: %s', $path),
            0,
            $previous
        );
    }
}
