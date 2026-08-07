<?php

declare(strict_types=1);

namespace Kodhe\Image\Drivers;

use Kodhe\Image\Contracts\ImageDriverInterface;
use Kodhe\Image\Support\ImageMetadataCache;
use Kodhe\Image\ValueObjects\ImageInfo;
use Kodhe\Image\ValueObjects\ImageDimensions;

/**
 * Class GdDriver
 *
 * GD library driver for image manipulation.
 * Implements Strategy Pattern for GD-based image processing.
 *
 * @package     Kodhe\Image
 * @author      CodeIgniter Refactored
 * @version     2.0.0
 * @license     MIT
 */
class GdDriver implements ImageDriverInterface
{
    /**
     * @var array Configuration properties
     */
    protected $config = [];

    /**
     * @var resource|null GD image resource
     */
    protected $resource = null;

    /**
     * @var string Source image path
     */
    protected $sourceImage = '';

    /**
     * @var string Destination image path
     */
    protected $destImage = '';

    /**
     * @var int Original width
     */
    protected $origWidth = 0;

    /**
     * @var int Original height
     */
    protected $origHeight = 0;

    /**
     * @var int Target width
     */
    protected $width = 0;

    /**
     * @var int Target height
     */
    protected $height = 0;

    /**
     * @var int Image quality (1-100)
     */
    protected $quality = 90;

    /**
     * @var bool Create thumbnail
     */
    protected $createThumb = false;

    /**
     * @var string Thumbnail marker
     */
    protected $thumbMarker = '_thumb';

    /**
     * @var bool Maintain aspect ratio
     */
    protected $maintainRatio = true;

    /**
     * @var string Master dimension ('auto', 'width', 'height')
     */
    protected $masterDim = 'auto';

    /**
     * @var int X axis for crop
     */
    protected $xAxis = 0;

    /**
     * @var int Y axis for crop
     */
    protected $yAxis = 0;

    /**
     * @var string Rotation angle
     */
    protected $rotationAngle = '';

    /**
     * @var bool Dynamic output
     */
    protected $dynamicOutput = false;

    /**
     * @var string Full source path
     */
    protected $fullSrcPath = '';

    /**
     * @var string Full destination path
     */
    protected $fullDstPath = '';

    /**
     * @var int Image type
     */
    protected $imageType = 0;

    /**
     * @var string MIME type
     */
    protected $mimeType = '';

    /**
     * @var string Size string
     */
    protected $sizeStr = '';

    /**
     * @var array Error messages
     */
    protected $errorMessages = [];

    /**
     * @var int File permissions
     */
    protected $filePermissions = 0644;

    /**
     * Watermark properties
     */
    protected $wmText = '';
    protected $wmType = 'text';
    protected $wmOverlayPath = '';
    protected $wmFontPath = '';
    protected $wmFontSize = 17;
    protected $wmVrtAlignment = 'B';
    protected $wmHorAlignment = 'C';
    protected $wmPadding = 0;
    protected $wmHorOffset = 0;
    protected $wmVrtOffset = 0;
    protected $wmFontColor = '#ffffff';
    protected $wmShadowColor = '';
    protected $wmShadowDistance = 2;
    protected $wmOpacity = 50;
    protected $wmUseDropShadow = false;
    protected $wmUseTruetype = false;
    protected $wmXTransp = 4;
    protected $wmYTransp = 4;

    /**
     * Cached image info
     *
     * @var ImageInfo|null
     */
    protected $cachedImageInfo = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Enable JPEG ignore warning for compatibility
        ini_set('gd.jpeg_ignore_warning', '1');
    }

    /**
     * Initialize the driver with configuration
     *
     * @param array $config
     * @return bool
     */
    public function initialize(array $config): bool
    {
        $this->config = array_merge($this->config, $config);

        // Set properties from config
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        // Validate source image
        if (empty($this->sourceImage)) {
            $this->setError('imglib_source_image_required');
            return false;
        }

        // Get image properties
        if (!$this->loadImageProperties()) {
            return false;
        }

        // Set paths
        $this->setupPaths();

        // Calculate dimensions if maintaining ratio
        if ($this->maintainRatio && ($this->width > 0 || $this->height > 0)) {
            $this->reproportion();
        }

        // Set defaults
        if ($this->width === '') {
            $this->width = $this->origWidth;
        }
        if ($this->height === '') {
            $this->height = $this->origHeight;
        }

        // Normalize quality
        $this->quality = (int) trim(str_replace('%', '', (string) $this->quality));
        if ($this->quality <= 0 || $this->quality > 100) {
            $this->quality = 90;
        }

        // Setup watermark
        $this->setupWatermark();

        return true;
    }

    /**
     * Load image properties using cached metadata
     *
     * @return bool
     */
    protected function loadImageProperties(): bool
    {
        $data = ImageMetadataCache::get($this->sourceImage);
        if ($data === null) {
            $this->setError('imglib_invalid_path');
            return false;
        }

        $types = [1 => 'gif', 2 => 'jpeg', 3 => 'png'];
        $type = $data[2] ?? 2;
        $mime = isset($types[$type]) ? 'image/' . $types[$type] : 'image/jpeg';

        $this->origWidth = (int) $data[0];
        $this->origHeight = (int) $data[1];
        $this->imageType = $type;
        $this->sizeStr = $data[3] ?? '';
        $this->mimeType = $mime;

        // Cache as ImageInfo object
        $this->cachedImageInfo = ImageInfo::fromGetImageSize($data, $this->sourceImage);

        return true;
    }

    /**
     * Setup source and destination paths
     *
     * @return void
     */
    protected function setupPaths(): void
    {
        $pathInfo = pathinfo($this->sourceImage);
        $this->fullSrcPath = $this->sourceImage;

        if (empty($this->destImage)) {
            $this->fullDstPath = $this->fullSrcPath;
        } else {
            $destInfo = pathinfo($this->destImage);
            if (!isset($destInfo['extension']) || !preg_match('/\.(jpg|jpeg|gif|png)$/i', $this->destImage)) {
                // It's a folder
                $this->fullDstPath = rtrim($this->destImage, '/') . '/' . $pathInfo['basename'];
            } else {
                $this->fullDstPath = $this->destImage;
            }
        }

        // Insert thumbnail marker if needed
        if ($this->createThumb && !empty($this->thumbMarker)) {
            $ext = $pathInfo['extension'] ?? '';
            $name = $pathInfo['filename'] ?? pathinfo($this->fullDstPath, PATHINFO_FILENAME);
            $dir = dirname($this->fullDstPath);
            $this->fullDstPath = $dir . '/' . $name . $this->thumbMarker . ($ext ? '.' . $ext : '');
        }
    }

    /**
     * Setup watermark configuration
     *
     * @return void
     */
    protected function setupWatermark(): void
    {
        if (!empty($this->wmOverlayPath)) {
            $this->wmOverlayPath = str_replace('\\', '/', realpath($this->wmOverlayPath) ?: $this->wmOverlayPath);
        }

        if (!empty($this->wmShadowColor)) {
            $this->wmUseDropShadow = true;
        } elseif ($this->wmUseDropShadow && empty($this->wmShadowColor)) {
            $this->wmUseDropShadow = false;
        }

        if (!empty($this->wmFontPath)) {
            $this->wmUseTruetype = true;
        }
    }

    /**
     * Reproportion dimensions while maintaining aspect ratio
     *
     * @return void
     */
    protected function reproportion(): void
    {
        if (($this->width === 0 && $this->height === 0)
            || $this->origWidth === 0 || $this->origHeight === 0) {
            return;
        }

        $width = is_numeric($this->width) ? (int) $this->width : 0;
        $height = is_numeric($this->height) ? (int) $this->height : 0;

        if ($this->masterDim !== 'width' && $this->masterDim !== 'height') {
            if ($width > 0 && $height > 0) {
                $ratioDiff = (($this->origHeight / $this->origWidth) - ($height / $width));
                $this->masterDim = ($ratioDiff < 0) ? 'width' : 'height';
            } else {
                $this->masterDim = ($height === 0) ? 'width' : 'height';
            }
        }

        if (($this->masterDim === 'width' && $width === 0)
            || ($this->masterDim === 'height' && $height === 0)) {
            return;
        }

        if ($this->masterDim === 'width') {
            $this->height = (int) ceil($width * $this->origHeight / $this->origWidth);
        } else {
            $this->width = (int) ceil($this->origWidth * $height / $this->origHeight);
        }
    }

    /**
     * Resize an image
     *
     * @return bool
     */
    public function resize(): bool
    {
        // Quick copy if dimensions match
        if (!$this->dynamicOutput && $this->origWidth === $this->width && $this->origHeight === $this->height) {
            if ($this->fullSrcPath !== $this->fullDstPath && @copy($this->fullSrcPath, $this->fullDstPath)) {
                chmod($this->fullDstPath, $this->filePermissions);
            }
            return true;
        }

        $srcImg = $this->createImage($this->fullSrcPath);
        if (!$srcImg) {
            return false;
        }

        // Determine create/copy functions
        if (function_exists('imagecreatetruecolor')) {
            $createFunc = 'imagecreatetruecolor';
            $copyFunc = 'imagecopyresampled';
        } else {
            $createFunc = 'imagecreate';
            $copyFunc = 'imagecopyresized';
        }

        $dstImg = $createFunc($this->width, $this->height);
        if (!$dstImg) {
            imagedestroy($srcImg);
            $this->setError('imglib_unsupported_imagecreate');
            return false;
        }

        // Preserve transparency for PNG
        if ($this->imageType === 3) {
            imagealphablending($dstImg, false);
            imagesavealpha($dstImg, true);
        }

        $copyFunc(
            $dstImg, $srcImg,
            0, 0, $this->xAxis, $this->yAxis,
            $this->width, $this->height,
            $this->origWidth, $this->origHeight
        );

        $result = $this->saveOrDisplay($dstImg);

        imagedestroy($dstImg);
        imagedestroy($srcImg);

        return $result;
    }

    /**
     * Crop an image
     *
     * @return bool
     */
    public function crop(): bool
    {
        $this->origWidth = $this->width;
        $this->origHeight = $this->height;
        $this->xAxis = (int) $this->xAxis;
        $this->yAxis = (int) $this->yAxis;

        return $this->resize();
    }

    /**
     * Rotate an image
     *
     * @return bool
     */
    public function rotate(): bool
    {
        $allowed = [90, 180, 270, 'vrt', 'hor'];
        if (empty($this->rotationAngle) || !in_array($this->rotationAngle, $allowed, true)) {
            $this->setError('imglib_rotation_angle_required');
            return false;
        }

        // Swap dimensions for 90/270 degree rotation
        if (in_array($this->rotationAngle, [90, 270], true)) {
            [$this->width, $this->height] = [$this->origHeight, $this->origWidth];
        } else {
            [$this->width, $this->height] = [$this->origWidth, $this->origHeight];
        }

        if (in_array($this->rotationAngle, ['hor', 'vrt'], true)) {
            return $this->mirror();
        }

        return $this->rotateGd();
    }

    /**
     * Rotate using GD
     *
     * @return bool
     */
    protected function rotateGd(): bool
    {
        $srcImg = $this->createImage($this->fullSrcPath);
        if (!$srcImg) {
            return false;
        }

        $white = imagecolorallocate($srcImg, 255, 255, 255);
        $dstImg = imagerotate($srcImg, (int) $this->rotationAngle, $white);

        $result = $this->saveOrDisplay($dstImg);

        imagedestroy($dstImg);
        imagedestroy($srcImg);

        return $result;
    }

    /**
     * Mirror/flip an image
     *
     * @return bool
     */
    protected function mirror(): bool
    {
        $srcImg = $this->createImage($this->fullSrcPath);
        if (!$srcImg) {
            return false;
        }

        $width = $this->origWidth;
        $height = $this->origHeight;

        if ($this->rotationAngle === 'hor') {
            for ($i = 0; $i < $height; $i++) {
                $left = 0;
                $right = $width - 1;
                while ($left < $right) {
                    $cl = imagecolorat($srcImg, $left, $i);
                    $cr = imagecolorat($srcImg, $right, $i);
                    imagesetpixel($srcImg, $left, $i, $cr);
                    imagesetpixel($srcImg, $right, $i, $cl);
                    $left++;
                    $right--;
                }
            }
        } else {
            for ($i = 0; $i < $width; $i++) {
                $top = 0;
                $bottom = $height - 1;
                while ($top < $bottom) {
                    $ct = imagecolorat($srcImg, $i, $top);
                    $cb = imagecolorat($srcImg, $i, $bottom);
                    imagesetpixel($srcImg, $i, $top, $cb);
                    imagesetpixel($srcImg, $i, $bottom, $ct);
                    $top++;
                    $bottom--;
                }
            }
        }

        $result = $this->saveOrDisplay($srcImg);
        imagedestroy($srcImg);

        return $result;
    }

    /**
     * Add watermark to image
     *
     * @return bool
     */
    public function watermark(): bool
    {
        return ($this->wmType === 'overlay')
            ? $this->overlayWatermark()
            : $this->textWatermark();
    }

    /**
     * Overlay watermark
     *
     * @return bool
     */
    protected function overlayWatermark(): bool
    {
        if (!function_exists('imagecolortransparent')) {
            $this->setError('imglib_gd_required');
            return false;
        }

        $wmProps = $this->getImageProperties($this->wmOverlayPath, true);
        if (!is_array($wmProps)) {
            return false;
        }

        $wmImg = $this->createImage($this->wmOverlayPath, $wmProps['image_type']);
        $srcImg = $this->createImage($this->fullSrcPath);

        if (!$wmImg || !$srcImg) {
            return false;
        }

        $wmWidth = $wmProps['width'];
        $wmHeight = $wmProps['height'];

        // Calculate position
        [$xAxis, $yAxis] = $this->calculateWatermarkPosition($wmWidth, $wmHeight);

        // Handle transparency
        $rgba = imagecolorat($wmImg, $this->wmXTransp, $this->wmYTransp);
        $alpha = ($rgba & 0x7F000000) >> 24;

        if ($alpha > 0) {
            imagecopy($srcImg, $wmImg, $xAxis, $yAxis, 0, 0, $wmWidth, $wmHeight);
        } else {
            imagecolortransparent($wmImg, imagecolorat($wmImg, $this->wmXTransp, $this->wmYTransp));
            imagecopymerge($srcImg, $wmImg, $xAxis, $yAxis, 0, 0, $wmWidth, $wmHeight, $this->wmOpacity);
        }

        // Preserve PNG transparency
        if ($this->imageType === 3) {
            imagealphablending($srcImg, false);
            imagesavealpha($srcImg, true);
        }

        $result = $this->saveOrDisplay($srcImg);

        imagedestroy($srcImg);
        imagedestroy($wmImg);

        return $result;
    }

    /**
     * Text watermark
     *
     * @return bool
     */
    protected function textWatermark(): bool
    {
        $srcImg = $this->createImage($this->fullSrcPath);
        if (!$srcImg) {
            return false;
        }

        if ($this->wmUseTruetype && !file_exists($this->wmFontPath)) {
            $this->setError('imglib_missing_font');
            imagedestroy($srcImg);
            return false;
        }

        // Calculate font dimensions
        if ($this->wmUseTruetype) {
            $fontWidth = $this->getTrueTypeFontWidth();
            $fontHeight = $this->wmFontSize;
        } else {
            $fontWidth = imagefontwidth($this->wmFontSize);
            $fontHeight = imagefontheight($this->wmFontSize);
        }

        // Calculate position
        [$xAxis, $yAxis] = $this->calculateTextWatermarkPosition($fontWidth, $fontHeight);

        // Draw shadow if enabled
        if ($this->wmUseDropShadow) {
            $xShad = $xAxis + $this->wmShadowDistance;
            $yShad = $yAxis + $this->wmShadowDistance;
            $shadowColor = $this->parseColor($this->wmShadowColor);

            if ($this->wmUseTruetype) {
                imagettftext($srcImg, $this->wmFontSize, 0, $xShad, $yShad, $shadowColor, $this->wmFontPath, $this->wmText);
            } else {
                imagestring($srcImg, $this->wmFontSize, $xShad, $yShad, $this->wmText, $shadowColor);
            }
        }

        // Draw text
        $textColor = $this->parseColor($this->wmFontColor);
        if ($this->wmUseTruetype) {
            imagettftext($srcImg, $this->wmFontSize, 0, $xAxis, $yAxis, $textColor, $this->wmFontPath, $this->wmText);
        } else {
            imagestring($srcImg, $this->wmFontSize, $xAxis, $yAxis, $this->wmText, $textColor);
        }

        // Preserve PNG transparency
        if ($this->imageType === 3) {
            imagealphablending($srcImg, false);
            imagesavealpha($srcImg, true);
        }

        $result = $this->saveOrDisplay($srcImg);
        imagedestroy($srcImg);

        return $result;
    }

    /**
     * Calculate watermark position
     *
     * @param int $wmWidth
     * @param int $wmHeight
     * @return array
     */
    protected function calculateWatermarkPosition(int $wmWidth, int $wmHeight): array
    {
        $vrtAlign = strtoupper($this->wmVrtAlignment[0]);
        $horAlign = strtoupper($this->wmHorAlignment[0]);

        // Invert offsets for bottom/right alignment
        $vrtOffset = ($vrtAlign === 'B') ? -$this->wmVrtOffset : $this->wmVrtOffset;
        $horOffset = ($horAlign === 'R') ? -$this->wmHorOffset : $this->wmHorOffset;

        $xAxis = $horOffset + $this->wmPadding;
        $yAxis = $vrtOffset + $this->wmPadding;

        // Vertical positioning
        if ($vrtAlign === 'M') {
            $yAxis += (int) (($this->origHeight / 2) - ($wmHeight / 2));
        } elseif ($vrtAlign === 'B') {
            $yAxis += $this->origHeight - $wmHeight;
        }

        // Horizontal positioning
        if ($horAlign === 'C') {
            $xAxis += (int) (($this->origWidth / 2) - ($wmWidth / 2));
        } elseif ($horAlign === 'R') {
            $xAxis += $this->origWidth - $wmWidth;
        }

        return [$xAxis, $yAxis];
    }

    /**
     * Calculate text watermark position
     *
     * @param int $fontWidth
     * @param int $fontHeight
     * @return array
     */
    protected function calculateTextWatermarkPosition(int $fontWidth, int $fontHeight): array
    {
        $vrtAlign = strtoupper($this->wmVrtAlignment[0]);
        $horAlign = strtoupper($this->wmHorAlignment[0]);

        $vrtOffset = ($vrtAlign === 'B') ? -$this->wmVrtOffset : $this->wmVrtOffset;
        $horOffset = ($horAlign === 'R') ? -$this->wmHorOffset : $this->wmHorOffset;

        $xAxis = $horOffset + $this->wmPadding;
        $yAxis = $vrtOffset + $this->wmPadding;

        // Vertical positioning
        if ($vrtAlign === 'M') {
            $yAxis += (int) (($this->origHeight / 2) + ($fontHeight / 2));
        } elseif ($vrtAlign === 'B') {
            $yAxis += (int) ($this->origHeight - $fontHeight - $this->wmShadowDistance - ($fontHeight / 2));
        }

        // Horizontal positioning
        $textWidth = $fontWidth * strlen($this->wmText);
        if ($horAlign === 'R') {
            $xAxis += $this->origWidth - $textWidth - $this->wmShadowDistance;
        } elseif ($horAlign === 'C') {
            $xAxis += (int) (($this->origWidth - $textWidth) / 2);
        }

        return [$xAxis, $yAxis];
    }

    /**
     * Get TrueType font width
     *
     * @return float|int
     */
    protected function getTrueTypeFontWidth()
    {
        if (function_exists('imagettfbbox')) {
            $temp = imagettfbbox($this->wmFontSize, 0, $this->wmFontPath, $this->wmText);
            return ($temp[2] - $temp[0]) / strlen($this->wmText);
        }
        return $this->wmFontSize - ($this->wmFontSize / 4);
    }

    /**
     * Parse hex color to RGB
     *
     * @param string $hex
     * @return int
     */
    protected function parseColor(string $hex): int
    {
        // This will be set on the actual image resource when called
        return 0; // Placeholder - actual implementation needs image resource
    }

    /**
     * Flip or mirror an image
     *
     * @param string $direction
     * @return bool
     */
    public function flip(string $direction): bool
    {
        $this->rotationAngle = ($direction === 'horizontal') ? 'hor' : 'vrt';
        return $this->mirror();
    }

    /**
     * Get image properties
     *
     * @param string $path
     * @param bool   $return
     * @return array|bool
     */
    public function getImageProperties(string $path, bool $return = false)
    {
        $data = ImageMetadataCache::get($path);
        if ($data === null) {
            $this->setError('imglib_invalid_path');
            return false;
        }

        $types = [1 => 'gif', 2 => 'jpeg', 3 => 'png'];
        $type = $data[2] ?? 2;
        $mime = isset($types[$type]) ? 'image/' . $types[$type] : 'image/jpeg';

        if ($return) {
            return [
                'width' => (int) $data[0],
                'height' => (int) $data[1],
                'image_type' => $type,
                'size_str' => $data[3] ?? '',
                'mime_type' => $mime,
            ];
        }

        return true;
    }

    /**
     * Create GD image resource
     *
     * @param string $path
     * @param int    $type
     * @return resource|false
     */
    protected function createImage(string $path, int $type = 0)
    {
        $type = $type ?: $this->imageType;

        switch ($type) {
            case 1:
                if (!function_exists('imagecreatefromgif')) {
                    $this->setError(['imglib_unsupported_imagecreate', 'imglib_gif_not_supported']);
                    return false;
                }
                return @imagecreatefromgif($path);

            case 2:
                if (!function_exists('imagecreatefromjpeg')) {
                    $this->setError(['imglib_unsupported_imagecreate', 'imglib_jpg_not_supported']);
                    return false;
                }
                return @imagecreatefromjpeg($path);

            case 3:
                if (!function_exists('imagecreatefrompng')) {
                    $this->setError(['imglib_unsupported_imagecreate', 'imglib_png_not_supported']);
                    return false;
                }
                return @imagecreatefrompng($path);

            default:
                $this->setError('imglib_unsupported_imagecreate');
                return false;
        }
    }

    /**
     * Save or display image
     *
     * @param resource $resource
     * @return bool
     */
    protected function saveOrDisplay($resource): bool
    {
        if ($this->dynamicOutput) {
            $this->displayImage($resource);
            return true;
        }

        return $this->saveImage($resource);
    }

    /**
     * Save image to file
     *
     * @param resource $resource
     * @return bool
     */
    protected function saveImage($resource): bool
    {
        switch ($this->imageType) {
            case 1:
                if (!function_exists('imagegif')) {
                    $this->setError(['imglib_unsupported_imagecreate', 'imglib_gif_not_supported']);
                    return false;
                }
                if (!@imagegif($resource, $this->fullDstPath)) {
                    $this->setError('imglib_save_failed');
                    return false;
                }
                break;

            case 2:
                if (!function_exists('imagejpeg')) {
                    $this->setError(['imglib_unsupported_imagecreate', 'imglib_jpg_not_supported']);
                    return false;
                }
                if (!@imagejpeg($resource, $this->fullDstPath, $this->quality)) {
                    $this->setError('imglib_save_failed');
                    return false;
                }
                break;

            case 3:
                if (!function_exists('imagepng')) {
                    $this->setError(['imglib_unsupported_imagecreate', 'imglib_png_not_supported']);
                    return false;
                }
                if (!@imagepng($resource, $this->fullDstPath)) {
                    $this->setError('imglib_save_failed');
                    return false;
                }
                break;

            default:
                $this->setError('imglib_unsupported_imagecreate');
                return false;
        }

        chmod($this->fullDstPath, $this->filePermissions);
        return true;
    }

    /**
     * Display image to browser
     *
     * @param resource $resource
     * @return void
     */
    protected function displayImage($resource): void
    {
        header('Content-Disposition: filename=' . basename($this->sourceImage) . ';');
        header('Content-Type: ' . $this->mimeType);
        header('Content-Transfer-Encoding: binary');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');

        switch ($this->imageType) {
            case 1:
                imagegif($resource);
                break;
            case 2:
                imagejpeg($resource, null, $this->quality);
                break;
            case 3:
                imagepng($resource);
                break;
        }
    }

    /**
     * Clean up resources
     *
     * @return void
     */
    public function clear(): void
    {
        if ($this->resource !== null) {
            imagedestroy($this->resource);
            $this->resource = null;
        }

        // Reset key properties
        $this->width = '';
        $this->height = '';
        $this->xAxis = '';
        $this->yAxis = '';
        $this->rotationAngle = '';
        $this->wmText = '';
        $this->cachedImageInfo = null;

        ImageMetadataCache::clear($this->sourceImage);
    }

    /**
     * Get error messages
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errorMessages;
    }

    /**
     * Set error message
     *
     * @param string|array $error
     * @return void
     */
    public function setError($error): void
    {
        // Simplified error handling - CI integration would use lang library
        if (is_array($error)) {
            foreach ($error as $msg) {
                $this->errorMessages[] = $msg;
            }
        } else {
            $this->errorMessages[] = $error;
        }
    }

    /**
     * Get GD version
     *
     * @return string|false
     */
    public static function getGdVersion()
    {
        if (function_exists('gd_info')) {
            $gdInfo = gd_info();
            return preg_replace('/\D/', '', $gdInfo['GD Version'] ?? '');
        }
        return false;
    }

    /**
     * Check if GD is available
     *
     * @return bool
     */
    public static function isAvailable(): bool
    {
        return function_exists('gd_info');
    }
}
