<?php

declare(strict_types=1);

namespace Kodhe\Framework\Zip\Exceptions;

/**
 * Exception thrown when archive operations fail
 */
class ArchiveWriteException extends ZipException
{
    public static function create(string $path, ?\Throwable $previous = null): self
    {
        return new self(
            sprintf('Failed to write archive: %s', $path),
            0,
            $previous
        );
    }
}
