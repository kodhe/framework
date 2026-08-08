<?php

declare(strict_types=1);

namespace Kodhe\Framework\Image\ValueObjects;

/**
 * Class ImageDimensions
 *
 * Value Object representing image dimensions.
 * Immutable value object for width and height.
 *
 * @package     Kodhe\Image
 * @author      CodeIgniter Refactored
 * @version     2.0.0
 * @license     MIT
 */
final class ImageDimensions
{
    /**
     * @var int
     */
    private $width;

    /**
     * @var int
     */
    private $height;

    /**
     * Constructor
     *
     * @param int $width
     * @param int $height
     */
    public function __construct(int $width, int $height)
    {
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * Get width
     *
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * Get height
     *
     * @return int
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * Create from array
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['width'] ?? 0),
            (int) ($data['height'] ?? 0)
        );
    }

    /**
     * Calculate aspect ratio
     *
     * @return float
     */
    public function getAspectRatio(): float
    {
        if ($this->height === 0) {
            return 0.0;
        }
        return $this->width / $this->height;
    }

    /**
     * Check if dimensions are valid
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->width > 0 && $this->height > 0;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}
