<?php

declare(strict_types=1);

namespace Kodhe\Framework\Zip\ValueObjects;

/**
 * Value object representing a ZIP archive entry
 */
class ZipEntry implements \Kodhe\Framework\Zip\Contracts\ZipEntryInterface
{
    private string $filename;
    private string $data;
    private int $crc32;
    private int $compressedSize;
    private int $uncompressedSize;
    private int $compressionMethod;
    private int $dosTime;
    private int $dosDate;
    private bool $isDirectory;
    private int $externalAttributes;

    public function __construct(
        string $filename,
        string $data = '',
        int $crc32 = 0,
        int $compressedSize = 0,
        int $uncompressedSize = 0,
        int $compressionMethod = 8,
        int $dosTime = 0,
        int $dosDate = 0,
        bool $isDirectory = false,
        int $externalAttributes = 32
    ) {
        $this->filename = $filename;
        $this->data = $data;
        $this->crc32 = $crc32;
        $this->compressedSize = $compressedSize;
        $this->uncompressedSize = $uncompressedSize;
        $this->compressionMethod = $compressionMethod;
        $this->dosTime = $dosTime;
        $this->dosDate = $dosDate;
        $this->isDirectory = $isDirectory;
        $this->externalAttributes = $isDirectory ? 16 : $externalAttributes;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getCrc32(): int
    {
        return $this->crc32;
    }

    public function getCompressedSize(): int
    {
        return $this->compressedSize;
    }

    public function getUncompressedSize(): int
    {
        return $this->uncompressedSize;
    }

    public function getCompressionMethod(): int
    {
        return $this->compressionMethod;
    }

    public function getDosTime(): int
    {
        return $this->dosTime;
    }

    public function getDosDate(): int
    {
        return $this->dosDate;
    }

    public function isDirectory(): bool
    {
        return $this->isDirectory;
    }

    public function getExternalAttributes(): int
    {
        return $this->externalAttributes;
    }

    /**
     * Create a directory entry
     */
    public static function directory(string $dirname, int $dosTime, int $dosDate): self
    {
        return new self(
            filename: rtrim($dirname, '/') . '/',
            data: '',
            crc32: 0,
            compressedSize: 0,
            uncompressedSize: 0,
            compressionMethod: 0,
            dosTime: $dosTime,
            dosDate: $dosDate,
            isDirectory: true,
            externalAttributes: 16
        );
    }

    /**
     * Create a file entry with compression
     */
    public static function file(
        string $filename,
        string $data,
        int $crc32,
        int $compressedSize,
        int $uncompressedSize,
        int $compressionMethod,
        int $dosTime,
        int $dosDate
    ): self {
        return new self(
            filename: $filename,
            data: $data,
            crc32: $crc32,
            compressedSize: $compressedSize,
            uncompressedSize: $uncompressedSize,
            compressionMethod: $compressionMethod,
            dosTime: $dosTime,
            dosDate: $dosDate,
            isDirectory: false,
            externalAttributes: 32
        );
    }
}
