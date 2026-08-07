<?php

declare(strict_types=1);

namespace Kodhe\Upload\Drivers;

use Kodhe\Upload\Contracts\FilenameStrategyInterface;

/**
 * Original Filename Strategy
 * 
 * Keeps the original filename
 * 
 * @package Kodhe\Upload\Drivers
 */
class OriginalFilenameStrategy implements FilenameStrategyInterface
{
    /**
     * Generate a filename
     *
     * @param string $originalName
     * @param string $extension
     * @param string $uploadPath
     * @return string
     */
    public function generate(string $originalName, string $extension, string $uploadPath): string
    {
        return $originalName . $extension;
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
        return file_exists($uploadPath . $filename);
    }
}
