<?php

declare(strict_types=1);

namespace Kodhe\Upload\Contracts;

/**
 * Upload Interface
 * 
 * Defines the contract for upload operations
 * 
 * @package Kodhe\Upload\Contracts
 */
interface UploadInterface
{
    /**
     * Initialize upload preferences
     *
     * @param array $config
     * @param bool $reset
     * @return self
     */
    public function initialize(array $config = [], bool $reset = true): self;

    /**
     * Perform the file upload
     *
     * @param string $field
     * @return bool
     */
    public function doUpload(string $field = 'userfile'): bool;

    /**
     * Get upload data
     *
     * @param string|null $index
     * @return mixed
     */
    public function data(?string $index = null);

    /**
     * Display error messages
     *
     * @param string $open
     * @param string $close
     * @return string
     */
    public function displayErrors(string $open = '<p>', string $close = '</p>'): string;

    /**
     * Set filename
     *
     * @param string $path
     * @param string $filename
     * @return string|false
     */
    public function setFilename(string $path, string $filename);

    /**
     * Set error message
     *
     * @param string|array $msg
     * @param string $logLevel
     * @return self
     */
    public function setError($msg, string $logLevel = 'error'): self;
}
