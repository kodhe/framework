<?php

declare(strict_types=0);

namespace Kodhe\Framework\Zip\Contracts;

/**
 * Interface for archive writers
 */
interface ArchiveWriterInterface
{
    /**
     * Write local file header and data
     */
    public function writeLocalHeader(ZipEntryInterface $entry): void;

    /**
     * Write file data
     */
    public function writeData(string $data): void;

    /**
     * Write central directory entry
     */
    public function writeCentralDirectory(ZipEntryInterface $entry): void;

    /**
     * Write end of central directory record
     */
    public function writeEndOfCentralDirectory(int $entries, int $centralDirSize, int $centralDirOffset): void;

    /**
     * Get the written data
     */
    public function getContents(): string;

    /**
     * Clear all data
     */
    public function clear(): void;
}
