<?php

declare(strict_types=1);

namespace Kodhe\Framework\Upload\Contracts;

/**
 * Storage Interface
 * 
 * Defines the contract for file storage operations
 * 
 * @package Kodhe\Upload\Contracts
 */
interface StorageInterface
{
    /**
     * Store a file
     *
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public function store(string $source, string $destination): bool;

    /**
     * Delete a file
     *
     * @param string $path
     * @return bool
     */
    public function delete(string $path): bool;

    /**
     * Check if file exists
     *
     * @param string $path
     * @return bool
     */
    public function exists(string $path): bool;

    /**
     * Get file size
     *
     * @param string $path
     * @return int
     */
    public function getSize(string $path): int;

    /**
     * Move uploaded file
     *
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public function moveUploadedFile(string $source, string $destination): bool;
}
