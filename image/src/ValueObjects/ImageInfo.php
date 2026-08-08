<?php

declare(strict_types=0);

namespace Kodhe\Framework\Image\ValueObjects;

/**
 * Class ImageInfo
 *
 * Value Object representing image metadata.
 * Immutable value object for image properties.
 *
 * @package     Kodhe\Image
 * @author      CodeIgniter Refactored
 * @version     2.0.0
 * @license     MIT
 */
final class ImageInfo
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
     * @var int
     */
    private $type;

    /**
     * @var string
     */
    private $mime;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string|null
     */
    private $attr;

    /**
     * Constructor
     *
     * @param int         $width
     * @param int         $height
     * @param int         $type
     * @param string      $mime
     * @param string      $path
     * @param string|null $attr
     */
    public function __construct(
        int $width,
        int $height,
        int $type,
        string $mime,
        string $path,
        ?string $attr = null
    ) {
        $this->width = $width;
        $this->height = $height;
        $this->type = $type;
        $this->mime = $mime;
        $this->path = $path;
        $this->attr = $attr;
    }

    /**
     * Create from getimagesize() result
     *
     * @param array  $data
     * @param string $path
     * @return self|null
     */
    public static function fromGetImageSize(array $data, string $path): ?self
    {
        if (!isset($data[0], $data[1], $data[2], $data['mime'])) {
            return null;
        }

        return new self(
            (int) $data[0],
            (int) $data[1],
            (int) $data[2],
            $data['mime'],
            $path,
            $data[3] ?? null
        );
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
     * Get image type
     *
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * Get MIME type
     *
     * @return string
     */
    public function getMime(): string
    {
        return $this->mime;
    }

    /**
     * Get file path
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get HTML attributes string
     *
     * @return string|null
     */
    public function getAttr(): ?string
    {
        return $this->attr;
    }

    /**
     * Get dimensions as Value Object
     *
     * @return ImageDimensions
     */
    public function getDimensions(): ImageDimensions
    {
        return new ImageDimensions($this->width, $this->height);
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
            'type' => $this->type,
            'mime' => $this->mime,
            'path' => $this->path,
            'attr' => $this->attr,
        ];
    }
}
