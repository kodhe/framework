<?php

declare(strict_types=1);

namespace Kodhe\Zip\Contracts;

/**
 * Interface representing a ZIP archive entry
 */
interface ZipEntryInterface
{
    /**
     * Get the filename/path of the entry
     */
    public function getFilename(): string;

    /**
     * Get the uncompressed data
     */
    public function getData(): string;

    /**
     * Get CRC32 checksum
     */
    public function getCrc32(): int;

    /**
     * Get compressed size
     */
    public function getCompressedSize(): int;

    /**
     * Get uncompressed size
     */
    public function getUncompressedSize(): int;

    /**
     * Get compression method (0 = stored, 8 = deflated)
     */
    public function getCompressionMethod(): int;

    /**
     * Get last modification time in DOS format
     */
    public function getDosTime(): int;

    /**
     * Get last modification date in DOS format
     */
    public function getDosDate(): int;

    /**
     * Check if this is a directory entry
     */
    public function isDirectory(): bool;

    /**
     * Get external file attributes
     */
    public function getExternalAttributes(): int;
}
