<?php

declare(strict_types=1);

namespace Kodhe\Upload\Drivers;

use Kodhe\Upload\Contracts\FilenameStrategyInterface;

/**
 * Encrypt Filename Strategy
 * 
 * Generates encrypted/random filenames
 * 
 * @package Kodhe\Upload\Drivers
 */
class EncryptFilenameStrategy implements FilenameStrategyInterface
{
    /**
     * Generate an encrypted filename
     *
     * @param string $originalName
     * @param string $extension
     * @param string $uploadPath
     * @return string
     */
    public function generate(string $originalName, string $extension, string $uploadPath): string
    {
        return md5(uniqid((string) mt_rand(), true)) . $extension;
    }

    /**
     * Check if filename exists
     *
     * @param string $filename
     * @param string $uploadPath
     * @return bool
     */
    public function exists(string $filename, string $uploadPath): bool
    {
        // Encrypted names should be unique, but check anyway
        return file_exists($uploadPath . $filename);
    }
}
