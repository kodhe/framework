<?php

declare(strict_types=1);

namespace Kodhe\Upload\Contracts;

/**
 * MIME Detector Interface
 * 
 * Defines the contract for MIME type detection
 * 
 * @package Kodhe\Upload\Contracts
 */
interface MimeDetectorInterface
{
    /**
     * Detect MIME type of a file
     *
     * @param string $filePath
     * @return string
     */
    public function detect(string $filePath): string;

    /**
     * Get MIME type by extension
     *
     * @param string $extension
     * @return string|null
     */
    public function getByExtension(string $extension): ?string;

    /**
     * Check if MIME type is valid for given extensions
     *
     * @param string $mimeType
     * @param array $allowedExtensions
     * @return bool
     */
    public function isValid(string $mimeType, array $allowedExtensions): bool;
}
