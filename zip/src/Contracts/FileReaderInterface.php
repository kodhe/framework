<?php

declare(strict_types=1);

namespace Kodhe\Framework\Zip\Contracts;

/**
 * Interface for file/directory readers
 */
interface FileReaderInterface
{
    /**
     * Read file contents
     *
     * @param string $path Path to the file
     * @return string|false File contents or false on failure
     */
    public function read(string $path): string|false;

    /**
     * Check if path exists
     */
    public function exists(string $path): bool;

    /**
     * Check if path is a directory
     */
    public function isDirectory(string $path): bool;

    /**
     * Get modification time
     */
    public function getModificationTime(string $path): int|false;

    /**
     * List directory contents
     *
     * @return array<string> Array of filenames
     */
    public function listDirectory(string $path): array;
}
