<?php

declare(strict_types=1);

namespace Kodhe\Upload\Storage;

use Kodhe\Upload\Contracts\StorageInterface;

/**
 * Local Storage
 * 
 * Handles local file system storage operations
 * 
 * @package Kodhe\Upload\Storage
 */
class LocalStorage implements StorageInterface
{
    /**
     * Store a file
     *
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public function store(string $source, string $destination): bool
    {
        // Ensure destination directory exists
        $dir = dirname($destination);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        // Try copy first
        if (@copy($source, $destination)) {
            return true;
        }

        // Fallback to move_uploaded_file for uploaded files
        return $this->moveUploadedFile($source, $destination);
    }

    /**
     * Delete a file
     *
     * @param string $path
     * @return bool
     */
    public function delete(string $path): bool
    {
        if (!$this->exists($path)) {
            return false;
        }

        return @unlink($path);
    }

    /**
     * Check if file exists
     *
     * @param string $path
     * @return bool
     */
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Get file size
     *
     * @param string $path
     * @return int
     */
    public function getSize(string $path): int
    {
        if (!$this->exists($path)) {
            return 0;
        }

        return filesize($path);
    }

    /**
     * Move uploaded file
     *
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public function moveUploadedFile(string $source, string $destination): bool
    {
        if (is_uploaded_file($source)) {
            return @move_uploaded_file($source, $destination);
        }

        // If not an uploaded file, try regular move
        return @rename($source, $destination);
    }

    /**
     * Check if path is writable
     *
     * @param string $path
     * @return bool
     */
    public function isWritable(string $path): bool
    {
        if (!is_dir($path)) {
            return false;
        }

        return is_writable($path);
    }

    /**
     * Create directory recursively
     *
     * @param string $path
     * @param int $permissions
     * @return bool
     */
    public function createDirectory(string $path, int $permissions = 0755): bool
    {
        if (is_dir($path)) {
            return true;
        }

        return @mkdir($path, $permissions, true);
    }
}
