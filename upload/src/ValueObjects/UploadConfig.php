<?php

declare(strict_types=0);

namespace Kodhe\Framework\Upload\ValueObjects;

/**
 * UploadConfig Value Object
 * 
 * Represents upload configuration settings
 * 
 * @package Kodhe\Upload\ValueObjects
 */
class UploadConfig
{
    /**
     * Maximum file size in KB
     *
     * @var int
     */
    private $maxSize = 0;

    /**
     * Maximum image width
     *
     * @var int
     */
    private $maxWidth = 0;

    /**
     * Maximum image height
     *
     * @var int
     */
    private $maxHeight = 0;

    /**
     * Minimum image width
     *
     * @var int
     */
    private $minWidth = 0;

    /**
     * Minimum image height
     *
     * @var int
     */
    private $minHeight = 0;

    /**
     * Maximum filename length
     *
     * @var int
     */
    private $maxFilename = 0;

    /**
     * Allowed file types
     *
     * @var array
     */
    private $allowedTypes = [];

    /**
     * Upload path
     *
     * @var string
     */
    private $uploadPath = '';

    /**
     * Overwrite existing files
     *
     * @var bool
     */
    private $overwrite = false;

    /**
     * Encrypt filename
     *
     * @var bool
     */
    private $encryptName = false;

    /**
     * Remove spaces from filename
     *
     * @var bool
     */
    private $removeSpaces = true;

    /**
     * Detect MIME type
     *
     * @var bool
     */
    private $detectMime = true;

    /**
     * XSS clean flag
     *
     * @var bool
     */
    private $xssClean = false;

    /**
     * Force extension to lowercase
     *
     * @var bool
     */
    private $fileExtToLower = false;

    /**
     * Mod MIME fix flag
     *
     * @var bool
     */
    private $modMimeFix = true;

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            $method = 'set' . str_replace('_', '', ucwords($key, '_'));
            if (method_exists($this, $method)) {
                $this->$method($value);
            } elseif (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Set max size
     *
     * @param int $size
     * @return self
     */
    public function setMaxSize(int $size): self
    {
        $this->maxSize = max(0, $size);
        return $this;
    }

    /**
     * Get max size
     *
     * @return int
     */
    public function getMaxSize(): int
    {
        return $this->maxSize;
    }

    /**
     * Set max width
     *
     * @param int $width
     * @return self
     */
    public function setMaxWidth(int $width): self
    {
        $this->maxWidth = max(0, $width);
        return $this;
    }

    /**
     * Get max width
     *
     * @return int
     */
    public function getMaxWidth(): int
    {
        return $this->maxWidth;
    }

    /**
     * Set max height
     *
     * @param int $height
     * @return self
     */
    public function setMaxHeight(int $height): self
    {
        $this->maxHeight = max(0, $height);
        return $this;
    }

    /**
     * Get max height
     *
     * @return int
     */
    public function getMaxHeight(): int
    {
        return $this->maxHeight;
    }

    /**
     * Set min width
     *
     * @param int $width
     * @return self
     */
    public function setMinWidth(int $width): self
    {
        $this->minWidth = max(0, $width);
        return $this;
    }

    /**
     * Set min height
     *
     * @param int $height
     * @return self
     */
    public function setMinHeight(int $height): self
    {
        $this->minHeight = max(0, $height);
        return $this;
    }

    /**
     * Set max filename length
     *
     * @param int $length
     * @return self
     */
    public function setMaxFilename(int $length): self
    {
        $this->maxFilename = max(0, $length);
        return $this;
    }

    /**
     * Set allowed types
     *
     * @param array|string $types
     * @return self
     */
    public function setAllowedTypes($types): self
    {
        if (is_string($types) && $types !== '*') {
            $types = explode('|', $types);
        }
        $this->allowedTypes = is_array($types) ? $types : [];
        return $this;
    }

    /**
     * Get allowed types
     *
     * @return array
     */
    public function getAllowedTypes(): array
    {
        return $this->allowedTypes;
    }

    /**
     * Set upload path
     *
     * @param string $path
     * @return self
     */
    public function setUploadPath(string $path): self
    {
        $this->uploadPath = rtrim($path, '/') . '/';
        return $this;
    }

    /**
     * Get upload path
     *
     * @return string
     */
    public function getUploadPath(): string
    {
        return $this->uploadPath;
    }

    /**
     * Set overwrite
     *
     * @param bool $overwrite
     * @return self
     */
    public function setOverwrite(bool $overwrite): self
    {
        $this->overwrite = $overwrite;
        return $this;
    }

    /**
     * Is overwrite enabled
     *
     * @return bool
     */
    public function isOverwrite(): bool
    {
        return $this->overwrite;
    }

    /**
     * Set encrypt name
     *
     * @param bool $encrypt
     * @return self
     */
    public function setEncryptName(bool $encrypt): self
    {
        $this->encryptName = $encrypt;
        return $this;
    }

    /**
     * Is encrypt name enabled
     *
     * @return bool
     */
    public function isEncryptName(): bool
    {
        return $this->encryptName;
    }

    /**
     * Set remove spaces
     *
     * @param bool $remove
     * @return self
     */
    public function setRemoveSpaces(bool $remove): self
    {
        $this->removeSpaces = $remove;
        return $this;
    }

    /**
     * Is remove spaces enabled
     *
     * @return bool
     */
    public function isRemoveSpaces(): bool
    {
        return $this->removeSpaces;
    }

    /**
     * Set detect mime
     *
     * @param bool $detect
     * @return self
     */
    public function setDetectMime(bool $detect): self
    {
        $this->detectMime = $detect;
        return $this;
    }

    /**
     * Is detect mime enabled
     *
     * @return bool
     */
    public function isDetectMime(): bool
    {
        return $this->detectMime;
    }

    /**
     * Set xss clean
     *
     * @param bool $clean
     * @return self
     */
    public function setXssClean(bool $clean): self
    {
        $this->xssClean = $clean;
        return $this;
    }

    /**
     * Is xss clean enabled
     *
     * @return bool
     */
    public function isXssClean(): bool
    {
        return $this->xssClean;
    }

    /**
     * Set file ext to lower
     *
     * @param bool $lower
     * @return self
     */
    public function setFileExtToLower(bool $lower): self
    {
        $this->fileExtToLower = $lower;
        return $this;
    }

    /**
     * Is file ext to lower enabled
     *
     * @return bool
     */
    public function isFileExtToLower(): bool
    {
        return $this->fileExtToLower;
    }

    /**
     * Set mod mime fix
     *
     * @param bool $fix
     * @return self
     */
    public function setModMimeFix(bool $fix): self
    {
        $this->modMimeFix = $fix;
        return $this;
    }

    /**
     * Is mod mime fix enabled
     *
     * @return bool
     */
    public function isModMimeFix(): bool
    {
        return $this->modMimeFix;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'max_size' => $this->maxSize,
            'max_width' => $this->maxWidth,
            'max_height' => $this->maxHeight,
            'min_width' => $this->minWidth,
            'min_height' => $this->minHeight,
            'max_filename' => $this->maxFilename,
            'allowed_types' => $this->allowedTypes,
            'upload_path' => $this->uploadPath,
            'overwrite' => $this->overwrite,
            'encrypt_name' => $this->encryptName,
            'remove_spaces' => $this->removeSpaces,
            'detect_mime' => $this->detectMime,
            'xss_clean' => $this->xssClean,
            'file_ext_tolower' => $this->fileExtToLower,
            'mod_mime_fix' => $this->modMimeFix,
        ];
    }
}
