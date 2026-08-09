<?php

declare(strict_types=1);

namespace Kodhe\Framework\Zip\Writers;

use Kodhe\Framework\Zip\Contracts\ArchiveWriterInterface;
use Kodhe\Framework\Zip\Contracts\ZipEntryInterface;
use Kodhe\Framework\Zip\Support\ByteUtils;

/**
 * In-memory archive writer implementation
 */
class MemoryWriter implements ArchiveWriterInterface
{
    private string $zipData = '';
    private string $centralDirectory = '';
    private int $entries = 0;
    private int $offset = 0;

    public function writeLocalHeader(ZipEntryInterface $entry): void
    {
        $filename = $entry->getFilename();
        $filenameLength = ByteUtils::strlen($filename);

        // Local file header signature
        $this->zipData .= "\x50\x4b\x03\x04";
        // Version needed to extract
        $this->zipData .= $entry->isDirectory() ? "\x0a\x00" : "\x14\x00";
        // General purpose bit flag
        $this->zipData .= "\x00\x00";
        // Compression method
        $this->zipData .= ByteUtils::packU16($entry->getCompressionMethod());
        // Last mod file time
        $this->zipData .= ByteUtils::packU16($entry->getDosTime());
        // Last mod file date
        $this->zipData .= ByteUtils::packU16($entry->getDosDate());
        // CRC-32
        $this->zipData .= ByteUtils::packU32($entry->getCrc32());
        // Compressed size
        $this->zipData .= ByteUtils::packU32($entry->getCompressedSize());
        // Uncompressed size
        $this->zipData .= ByteUtils::packU32($entry->getUncompressedSize());
        // File name length
        $this->zipData .= ByteUtils::packU16($filenameLength);
        // Extra field length
        $this->zipData .= "\x00\x00";
        // File name
        $this->zipData .= $filename;
    }

    public function writeData(string $data): void
    {
        $this->zipData .= $data;
    }

    public function writeCentralDirectory(ZipEntryInterface $entry): void
    {
        $filename = $entry->getFilename();
        $filenameLength = ByteUtils::strlen($filename);

        // Central directory file header signature
        $this->centralDirectory .= "\x50\x4b\x01\x02";
        // Version made by
        $this->centralDirectory .= "\x00\x00";
        // Version needed to extract
        $this->centralDirectory .= $entry->isDirectory() ? "\x0a\x00" : "\x14\x00";
        // General purpose bit flag
        $this->centralDirectory .= "\x00\x00";
        // Compression method
        $this->centralDirectory .= ByteUtils::packU16($entry->getCompressionMethod());
        // Last mod file time
        $this->centralDirectory .= ByteUtils::packU16($entry->getDosTime());
        // Last mod file date
        $this->centralDirectory .= ByteUtils::packU16($entry->getDosDate());
        // CRC-32
        $this->centralDirectory .= ByteUtils::packU32($entry->getCrc32());
        // Compressed size
        $this->centralDirectory .= ByteUtils::packU32($entry->getCompressedSize());
        // Uncompressed size
        $this->centralDirectory .= ByteUtils::packU32($entry->getUncompressedSize());
        // File name length
        $this->centralDirectory .= ByteUtils::packU16($filenameLength);
        // Extra field length
        $this->centralDirectory .= "\x00\x00";
        // File comment length
        $this->centralDirectory .= "\x00\x00";
        // Disk number start
        $this->centralDirectory .= "\x00\x00";
        // Internal file attributes
        $this->centralDirectory .= "\x00\x00";
        // External file attributes
        $this->centralDirectory .= ByteUtils::packU32($entry->getExternalAttributes());
        // Relative offset of local header
        $this->centralDirectory .= ByteUtils::packU32($entry->isDirectory() ? $this->offset - strlen($filename) - 30 : $this->offset);
        // File name
        $this->centralDirectory .= $filename;

        $this->entries++;
    }

    public function writeEndOfCentralDirectory(int $entries, int $centralDirSize, int $centralDirOffset): void
    {
        // End of central directory signature
        $this->zipData .= "\x50\x4b\x05\x06";
        // Number of this disk
        $this->zipData .= "\x00\x00";
        // Disk where central directory starts
        $this->zipData .= "\x00\x00";
        // Number of central directory records on this disk
        $this->zipData .= ByteUtils::packU16($entries);
        // Total number of central directory records
        $this->zipData .= ByteUtils::packU16($entries);
        // Size of central directory
        $this->zipData .= ByteUtils::packU32($centralDirSize);
        // Offset of start of central directory
        $this->zipData .= ByteUtils::packU32($centralDirOffset);
        // Comment length
        $this->zipData .= "\x00\x00";
    }

    public function getContents(): string
    {
        if ($this->entries === 0) {
            return '';
        }

        $centralDirOffset = ByteUtils::strlen($this->zipData);
        $centralDirSize = ByteUtils::strlen($this->centralDirectory);

        return $this->zipData
            . $this->centralDirectory
            . $this->buildEndOfCentralDirectory($this->entries, $centralDirSize, $centralDirOffset);
    }

    private function buildEndOfCentralDirectory(int $entries, int $centralDirSize, int $centralDirOffset): string
    {
        $data = "\x50\x4b\x05\x06"; // End of central directory signature
        $data .= "\x00\x00"; // Number of this disk
        $data .= "\x00\x00"; // Disk where central directory starts
        $data .= ByteUtils::packU16($entries); // Number of central directory records on this disk
        $data .= ByteUtils::packU16($entries); // Total number of central directory records
        $data .= ByteUtils::packU32($centralDirSize); // Size of central directory
        $data .= ByteUtils::packU32($centralDirOffset); // Offset of start of central directory
        $data .= "\x00\x00"; // Comment length

        return $data;
    }

    public function clear(): void
    {
        $this->zipData = '';
        $this->centralDirectory = '';
        $this->entries = 0;
        $this->offset = 0;
    }

    public function getEntries(): int
    {
        return $this->entries;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function setOffset(int $offset): void
    {
        $this->offset = $offset;
    }

    public function incrementOffset(int $bytes): void
    {
        $this->offset += $bytes;
    }
}
