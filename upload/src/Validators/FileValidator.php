<?php

declare(strict_types=0);

namespace Kodhe\Framework\Upload\Validators;

use Kodhe\Framework\Upload\Contracts\ValidatorInterface;
use Kodhe\Framework\Upload\Support\MimeCache;

/**
 * File Validator
 * 
 * Validates uploaded files based on rules
 * 
 * @package Kodhe\Upload\Validators
 */
class FileValidator implements ValidatorInterface
{
    /**
     * Validation errors
     *
     * @var array
     */
    private $errors = [];

    /**
     * Validation rules
     *
     * @var array
     */
    private $rules = [];

    /**
     * MIME cache instance
     *
     * @var MimeCache|null
     */
    private $mimeCache;

    /**
     * Constructor
     *
     * @param array $rules
     * @param MimeCache|null $mimeCache
     */
    public function __construct(array $rules = [], ?MimeCache $mimeCache = null)
    {
        $this->rules = $rules;
        $this->mimeCache = $mimeCache ?? new MimeCache();
    }

    /**
     * Validate a file
     *
     * @param string $filePath
     * @param array $rules
     * @return bool
     */
    public function validate(string $filePath, array $rules = []): bool
    {
        $this->errors = [];
        $rules = empty($rules) ? $this->rules : $rules;

        if (!file_exists($filePath)) {
            $this->errors[] = 'File does not exist';
            return false;
        }

        // Lazy validation - only validate what's configured
        if (isset($rules['max_size']) && $rules['max_size'] > 0) {
            if (!$this->validateSize($filePath, $rules['max_size'])) {
                return false;
            }
        }

        if (isset($rules['allowed_types']) && !empty($rules['allowed_types'])) {
            if (!$this->validateType($filePath, $rules['allowed_types'])) {
                return false;
            }
        }

        if (isset($rules['max_width']) || isset($rules['max_height']) ||
            isset($rules['min_width']) || isset($rules['min_height'])) {
            if (!$this->validateDimensions($filePath, $rules)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate file size
     *
     * @param string $filePath
     * @param int $maxSize
     * @return bool
     */
    private function validateSize(string $filePath, int $maxSize): bool
    {
        $size = filesize($filePath);
        $maxSizeBytes = $maxSize * 1024; // Convert KB to bytes

        if ($size > $maxSizeBytes) {
            $this->errors[] = 'The file exceeds the maximum allowed size';
            return false;
        }

        return true;
    }

    /**
     * Validate file type
     *
     * @param string $filePath
     * @param array $allowedTypes
     * @return bool
     */
    private function validateType(string $filePath, array $allowedTypes): bool
    {
        if ($allowedTypes === ['*']) {
            return true;
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        // Check extension first
        if (!in_array($extension, $allowedTypes, true)) {
            $this->errors[] = 'The file type is not allowed';
            return false;
        }

        // For images, verify it's actually an image
        if (in_array($extension, ['gif', 'jpg', 'jpeg', 'jpe', 'png'], true)) {
            if (@getimagesize($filePath) === false) {
                $this->errors[] = 'The file is not a valid image';
                return false;
            }
        }

        // MIME type validation
        $mimeType = $this->mimeCache::detect($filePath);
        if (!$this->mimeCache::isValid($mimeType, $allowedTypes)) {
            $this->errors[] = 'The file MIME type is not allowed';
            return false;
        }

        return true;
    }

    /**
     * Validate image dimensions
     *
     * @param string $filePath
     * @param array $rules
     * @return bool
     */
    private function validateDimensions(string $filePath, array $rules): bool
    {
        if (!function_exists('getimagesize')) {
            return true;
        }

        $dimensions = @getimagesize($filePath);
        if ($dimensions === false) {
            return true; // Not an image, skip dimension validation
        }

        $width = $dimensions[0];
        $height = $dimensions[1];

        if (isset($rules['max_width']) && $rules['max_width'] > 0 && $width > $rules['max_width']) {
            $this->errors[] = 'The image width exceeds the maximum allowed';
            return false;
        }

        if (isset($rules['max_height']) && $rules['max_height'] > 0 && $height > $rules['max_height']) {
            $this->errors[] = 'The image height exceeds the maximum allowed';
            return false;
        }

        if (isset($rules['min_width']) && $rules['min_width'] > 0 && $width < $rules['min_width']) {
            $this->errors[] = 'The image width is below the minimum allowed';
            return false;
        }

        if (isset($rules['min_height']) && $rules['min_height'] > 0 && $height < $rules['min_height']) {
            $this->errors[] = 'The image height is below the minimum allowed';
            return false;
        }

        return true;
    }

    /**
     * Get validation errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if validation passed
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }

    /**
     * Set rules
     *
     * @param array $rules
     * @return self
     */
    public function setRules(array $rules): self
    {
        $this->rules = $rules;
        return $this;
    }
}
