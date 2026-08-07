<?php

declare(strict_types=1);

namespace Kodhe\Zip\Archive;

use Kodhe\Zip\Contracts\CompressionStrategyInterface;
use Kodhe\Zip\Contracts\FileReaderInterface;
use Kodhe\Zip\Contracts\ArchiveWriterInterface;
use Kodhe\Zip\ValueObjects\ZipEntry;
use Kodhe\Zip\Support\ByteUtils;
use Kodhe\Zip\Factory\CompressionFactory;

/**
 * Builder class for creating ZIP archives
 */
class ArchiveBuilder
{
    private ArchiveWriterInterface $writer;
    private FileReaderInterface $fileReader;
    private CompressionStrategyInterface $compressionStrategy;
    private int $compressionLevel = 2;
    private int $currentTime;
    private array $entries = [];

    public function __construct(
        ArchiveWriterInterface $writer,
        FileReaderInterface $fileReader,
        ?CompressionStrategyInterface $compressionStrategy = null
    ) {
        $this->writer = $writer;
        $this->fileReader = $fileReader;
        $this->compressionStrategy = $compressionStrategy ?? CompressionFactory::create($this->compressionLevel);
        $this->currentTime = time();
    }

    /**
     * Set compression level (0-9)
     */
    public function setCompressionLevel(int $level): self
    {
        $this->compressionLevel = max(0, min(9, $level));
        $this->compressionStrategy = CompressionFactory::create($this->compressionLevel);
        return $this;
    }

    /**
     * Get current compression strategy
     */
    public function getCompressionStrategy(): CompressionStrategyInterface
    {
        return $this->compressionStrategy;
    }

    /**
     * Add a directory entry
     */
    public function addDirectory(string $dirname): self
    {
        $dirname = ByteUtils::normalizePath($dirname);
        if (!preg_match('|.*/$|', $dirname)) {
            $dirname .= '/';
        }

        $dosTime = ByteUtils::unixToDosTime($this->currentTime);
        $entry = ZipEntry::directory($dirname, $dosTime['time'], $dosTime['date']);
        $this->entries[] = $entry;

        return $this;
    }

    /**
     * Add file data with optional compression
     */
    public function addFile(string $filepath, string $data): self
    {
        $filepath = ByteUtils::normalizePath($filepath);
        
        // Get modification time
        $timestamp = $this->fileReader->getModificationTime($filepath);
        if ($timestamp === false) {
            $timestamp = $this->currentTime;
        }
        $dosTime = ByteUtils::unixToDosTime((int)$timestamp);

        // Calculate CRC32
        $crc32 = crc32($data);
        $uncompressedSize = ByteUtils::strlen($data);

        // Compress data
        $result = $this->compressionStrategy->compress($data);
        $compressedData = $result['compressed'];
        $compressedSize = $result['size'];
        $compressionMethod = $result['method'];

        $entry = ZipEntry::file(
            filename: $filepath,
            data: $compressedData,
            crc32: $crc32,
            compressedSize: $compressedSize,
            uncompressedSize: $uncompressedSize,
            compressionMethod: $compressionMethod,
            dosTime: $dosTime['time'],
            dosDate: $dosTime['date']
        );

        $this->entries[] = $entry;

        return $this;
    }

    /**
     * Read file from filesystem and add to archive
     */
    public function readFile(string $path, ?string $archivePath = null): bool
    {
        $data = $this->fileReader->read($path);
        if ($data === false) {
            return false;
        }

        if ($archivePath === null) {
            $archivePath = preg_replace('|.*/(.+)|', '\\1', ByteUtils::normalizePath($path));
        }

        $this->addFile($archivePath, $data);
        return true;
    }

    /**
     * Recursively read directory and add to archive
     */
    public function readDirectory(string $path, bool $preservePath = true, ?string $rootPath = null): bool
    {
        $path = rtrim($path, '/\\') . DIRECTORY_SEPARATOR;

        if (!$this->fileReader->isDirectory($path)) {
            return false;
        }

        // Set the original directory root for child dir's to use as relative
        if ($rootPath === null) {
            $rootPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, dirname($path)) . DIRECTORY_SEPARATOR;
        }

        $files = $this->fileReader->listDirectory($path);

        foreach ($files as $file) {
            if ($file[0] === '.') {
                continue;
            }

            $fullPath = $path . $file;

            if ($this->fileReader->isDirectory($fullPath)) {
                $this->readDirectory($fullPath . DIRECTORY_SEPARATOR, $preservePath, $rootPath);
            } else {
                $data = $this->fileReader->read($fullPath);
                if ($data !== false) {
                    $name = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
                    if ($preservePath === false) {
                        $name = str_replace($rootPath, '', $name);
                    }
                    $this->addFile($name . $file, $data);
                }
            }
        }

        return true;
    }

    /**
     * Build the archive
     */
    public function build(): string
    {
        foreach ($this->entries as $entry) {
            $offsetBefore = ByteUtils::strlen($this->writer->getContents());
            
            // Write local header
            $this->writer->writeLocalHeader($entry);
            
            // Write data (if not directory)
            if (!$entry->isDirectory()) {
                $this->writer->writeData($entry->getData());
            }
        }

        // Write central directory entries
        foreach ($this->entries as $entry) {
            $this->writer->writeCentralDirectory($entry);
        }

        return $this->writer->getContents();
    }

    /**
     * Get number of entries
     */
    public function getEntryCount(): int
    {
        return count($this->entries);
    }

    /**
     * Clear all entries
     */
    public function clear(): self
    {
        $this->entries = [];
        $this->writer->clear();
        return $this;
    }

    /**
     * Get all entries
     */
    public function getEntries(): array
    {
        return $this->entries;
    }
}
