<?php

declare(strict_types=1);

namespace Kodhe\Zip\Exceptions;

/**
 * Exception thrown when file operations fail
 */
class FileReadException extends ZipException
{
    public static function create(string $path, ?\Throwable $previous = null): self
    {
        return new self(
            sprintf('Failed to read file: %s', $path),
            0,
            $previous
        );
    }
}
