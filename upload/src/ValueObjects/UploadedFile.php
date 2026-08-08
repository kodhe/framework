<?php

declare(strict_types=1);

namespace Kodhe\Framework\Upload\ValueObjects;

/**
 * UploadedFile Value Object
 * 
 * Represents an uploaded file with its metadata
 * 
 * @package Kodhe\Upload\ValueObjects
 */
class UploadedFile
{
    /**
     * Temporary file path
     *
     * @var string
     */
    private $tmpName;

    /**
     * Original file name
     *
     * @var string
     */
    private $originalName;

    /**
     * File size in bytes
     *
     * @var int
     */
    private $size;

    /**
     * MIME type
     *
     * @var string
     */
    private $mimeType;

    /**
     * Upload error code
     *
     * @var int
     */
    private $error;

    /**
     * Constructor
     *
     * @param string $tmpName
     * @param string $originalName
     * @param int $size
     * @param string $mimeType
     * @param int $error
     */
    public function __construct(
        string $tmpName,
        string $originalName,
        int $size,
        string $mimeType,
        int $error
    ) {
        $this->tmpName = $tmpName;
        $this->originalName = $originalName;
        $this->size = $size;
        $this->mimeType = $mimeType;
        $this->error = $error;
    }

    /**
     * Create from $_FILES array
     *
     * @param array $fileData
     * @return self
     */
    public static function fromArray(array $fileData): self
    {
        return new self(
            $fileData['tmp_name'] ?? '',
            $fileData['name'] ?? '',
            $fileData['size'] ?? 0,
            $fileData['type'] ?? '',
            $fileData['error'] ?? UPLOAD_ERR_NO_FILE
        );
    }

    /**
     * Get temporary file path
     *
     * @return string
     */
    public function getTmpName(): string
    {
        return $this->tmpName;
    }

    /**
     * Get original file name
     *
     * @return string
     */
    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    /**
     * Get file size
     *
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Get MIME type
     *
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Get upload error code
     *
     * @return int
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * Check if upload has no error
     *
     * @return bool
     */
    public function hasNoError(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    /**
     * Get file extension
     *
     * @return string
     */
    public function getExtension(): string
    {
        $ext = pathinfo($this->originalName, PATHINFO_EXTENSION);
        return $ext !== '' ? '.' . strtolower($ext) : '';
    }

    /**
     * Get file name without extension
     *
     * @return string
     */
    public function getNameWithoutExtension(): string
    {
        return pathinfo($this->originalName, PATHINFO_FILENAME);
    }
}
