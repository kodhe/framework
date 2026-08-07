<?php

declare(strict_types=1);

namespace Kodhe\Upload\Drivers;

use Kodhe\Upload\Contracts\FilenameStrategyInterface;

/**
 * Increment Filename Strategy
 * 
 * Adds increment number for duplicate files
 * 
 * @package Kodhe\Upload\Drivers
 */
class IncrementFilenameStrategy implements FilenameStrategyInterface
{
    /**
     * Maximum increment attempts
     *
     * @var int
     */
    private $maxIncrement = 100;

    /**
     * Constructor
     *
     * @param int $maxIncrement
     */
    public function __construct(int $maxIncrement = 100)
    {
        $this->maxIncrement = $maxIncrement;
    }

    /**
     * Generate a filename with increment if exists
     *
     * @param string $originalName
     * @param string $extension
     * @param string $uploadPath
     * @return string|false Returns false if no unique name found
     */
    public function generate(string $originalName, string $extension, string $uploadPath): string
    {
        $filename = $originalName . $extension;
        
        // If file doesn't exist, return as is
        if (!$this->exists($filename, $uploadPath)) {
            return $filename;
        }

        // Try to find unique name with increment
        for ($i = 1; $i < $this->maxIncrement; $i++) {
            $newFilename = $originalName . $i . $extension;
            if (!$this->exists($newFilename, $uploadPath)) {
                return $newFilename;
            }
        }

        return '';
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

    /**
     * Set maximum increment value
     *
     * @param int $max
     * @return self
     */
    public function setMaxIncrement(int $max): self
    {
        $this->maxIncrement = $max;
        return $this;
    }
}
