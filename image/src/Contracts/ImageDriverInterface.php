<?php

declare(strict_types=1);

namespace Kodhe\Framework\Image\Contracts;

/**
 * Interface ImageDriverInterface
 *
 * Defines the contract for image manipulation drivers.
 * Implements Strategy Pattern for different image processing libraries.
 *
 * @package     Kodhe\Image
 * @author      CodeIgniter Refactored
 * @version     2.0.0
 * @license     MIT
 */
interface ImageDriverInterface
{
    /**
     * Initialize the driver with configuration
     *
     * @param array $config
     * @return bool
     */
    public function initialize(array $config): bool;

    /**
     * Resize an image
     *
     * @return bool
     */
    public function resize(): bool;

    /**
     * Crop an image
     *
     * @return bool
     */
    public function crop(): bool;

    /**
     * Rotate an image
     *
     * @return bool
     */
    public function rotate(): bool;

    /**
     * Add watermark to an image
     *
     * @return bool
     */
    public function watermark(): bool;

    /**
     * Flip or mirror an image
     *
     * @param string $direction 'horizontal' or 'vertical'
     * @return bool
     */
    public function flip(string $direction): bool;

    /**
     * Get image properties
     *
     * @param string $path
     * @param bool   $return
     * @return array|bool
     */
    public function getImageProperties(string $path, bool $return = false);

    /**
     * Clean up resources
     *
     * @return void
     */
    public function clear(): void;

    /**
     * Get error messages
     *
     * @return array
     */
    public function getErrors(): array;

    /**
     * Set error message
     *
     * @param string $error
     * @return void
     */
    public function setError(string $error): void;
}
