<?php

declare(strict_types=1);

namespace Kodhe\Upload\Contracts;

/**
 * Filename Strategy Interface
 * 
 * Defines the contract for filename generation strategies
 * 
 * @package Kodhe\Upload\Contracts
 */
interface FilenameStrategyInterface
{
    /**
     * Generate a filename
     *
     * @param string $originalName
     * @param string $extension
     * @param string $uploadPath
     * @return string
     */
    public function generate(string $originalName, string $extension, string $uploadPath): string;

    /**
     * Check if filename exists
     *
     * @param string $filename
     * @param string $uploadPath
     * @return bool
     */
    public function exists(string $filename, string $uploadPath): bool;
}
