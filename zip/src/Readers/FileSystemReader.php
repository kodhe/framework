<?php

declare(strict_types=0);

namespace Kodhe\Framework\Zip\Readers;

use Kodhe\Framework\Zip\Contracts\FileReaderInterface;
use Kodhe\Framework\Zip\Exceptions\FileReadException;
use Kodhe\Framework\Zip\Exceptions\DirectoryReadException;

/**
 * Default file system reader implementation
 */
class FileSystemReader implements FileReaderInterface
{
    public function read(string $path): string|false
    {
        if (!$this->exists($path)) {
            return false;
        }

        $data = file_get_contents($path);
        if ($data === false) {
            throw FileReadException::create($path);
        }

        return $data;
    }

    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    public function isDirectory(string $path): bool
    {
        return is_dir($path);
    }

    public function getModificationTime(string $path): int|false
    {
        if (!$this->exists($path)) {
            return false;
        }

        $mtime = filemtime($path);
        return $mtime !== false ? (int)$mtime : false;
    }

    public function listDirectory(string $path): array
    {
        if (!$this->isDirectory($path)) {
            throw DirectoryReadException::create($path);
        }

        $files = [];
        $fp = @opendir($path);

        if ($fp === false) {
            throw DirectoryReadException::create($path);
        }

        while (($file = readdir($fp)) !== false) {
            if ($file[0] !== '.') {
                $files[] = $file;
            }
        }

        closedir($fp);
        return $files;
    }
}
