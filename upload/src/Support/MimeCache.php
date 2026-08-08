<?php

declare(strict_types=0);

namespace Kodhe\Framework\Upload\Support;

/**
 * MIME Type Cache
 * 
 * Provides cached MIME type detection for performance
 * 
 * @package Kodhe\Upload\Support
 */
class MimeCache
{
    /**
     * MIME types cache
     *
     * @var array|null
     */
    private static $cache = null;

    /**
     * File info resource
     *
     * @var resource|null
     */
    private static $finfoResource = null;

    /**
     * Get MIME types from system
     *
     * @return array
     */
    public static function getMimes(): array
    {
        if (self::$cache === null) {
            self::$cache = self::loadMimes();
        }
        return self::$cache;
    }

    /**
     * Load MIME types
     *
     * @return array
     */
    private static function loadMimes(): array
    {
        // Try to load from CI3 mimes file or use default
        if (function_exists('get_mimes')) {
            return get_mimes();
        }

        // Default MIME types
        return [
            'jpg' => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'jpe' => ['image/jpeg', 'image/pjpeg'],
            'png' => ['image/png', 'image/x-png'],
            'gif' => ['image/gif'],
            'bmp' => ['image/bmp'],
            'tiff' => ['image/tiff'],
            'tif' => ['image/tiff'],
            'ico' => ['image/x-icon'],
            'webp' => ['image/webp'],
            'svg' => ['image/svg+xml'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'ppt' => ['application/vnd.ms-powerpoint'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'txt' => ['text/plain'],
            'csv' => ['text/csv', 'text/plain'],
            'rtf' => ['application/rtf'],
            'zip' => ['application/zip'],
            'rar' => ['application/x-rar-compressed'],
            'tar' => ['application/x-tar'],
            'gz' => ['application/gzip'],
            'mp3' => ['audio/mpeg'],
            'wav' => ['audio/wav'],
            'mp4' => ['video/mp4'],
            'avi' => ['video/x-msvideo'],
            'mov' => ['video/quicktime'],
            'wmv' => ['video/x-ms-wmv'],
            'flv' => ['video/x-flv'],
            'html' => ['text/html'],
            'htm' => ['text/html'],
            'css' => ['text/css'],
            'js' => ['application/javascript', 'text/javascript'],
            'json' => ['application/json'],
            'xml' => ['application/xml', 'text/xml'],
        ];
    }

    /**
     * Detect MIME type using fileinfo with caching
     *
     * @param string $filePath
     * @return string
     */
    public static function detect(string $filePath): string
    {
        if (!file_exists($filePath)) {
            return 'application/octet-stream';
        }

        // Try finfo first (most reliable)
        if (function_exists('finfo_file')) {
            if (self::$finfoResource === null) {
                self::$finfoResource = @finfo_open(FILEINFO_MIME);
            }
            
            if (is_resource(self::$finfoResource)) {
                $mime = @finfo_file(self::$finfoResource, $filePath);
                if ($mime !== false) {
                    // Extract just the MIME type without charset
                    if (($pos = strpos($mime, ';')) !== false) {
                        $mime = substr($mime, 0, $pos);
                    }
                    return trim($mime);
                }
            }
        }

        // Fallback to mime_content_type
        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($filePath);
            if ($mime !== false) {
                return $mime;
            }
        }

        return 'application/octet-stream';
    }

    /**
     * Get MIME type by extension from cache
     *
     * @param string $extension
     * @return string|null
     */
    public static function getByExtension(string $extension): ?string
    {
        $mimes = self::getMimes();
        $ext = strtolower(ltrim($extension, '.'));
        
        if (isset($mimes[$ext])) {
            $mime = $mimes[$ext];
            return is_array($mime) ? $mime[0] : $mime;
        }

        return null;
    }

    /**
     * Check if MIME type matches allowed extensions
     *
     * @param string $mimeType
     * @param array $allowedExtensions
     * @return bool
     */
    public static function isValid(string $mimeType, array $allowedExtensions): bool
    {
        $mimes = self::getMimes();
        
        foreach ($allowedExtensions as $ext) {
            $ext = strtolower(ltrim($ext, '.'));
            if (!isset($mimes[$ext])) {
                continue;
            }
            
            $allowedMimes = $mimes[$ext];
            if (!is_array($allowedMimes)) {
                $allowedMimes = [$allowedMimes];
            }
            
            if (in_array($mimeType, $allowedMimes, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Clear cache
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$cache = null;
        if (self::$finfoResource !== null && is_resource(self::$finfoResource)) {
            @finfo_close(self::$finfoResource);
            self::$finfoResource = null;
        }
    }

    /**
     * Destructor - cleanup
     */
    public function __destruct()
    {
        if (self::$finfoResource !== null && is_resource(self::$finfoResource)) {
            @finfo_close(self::$finfoResource);
        }
    }
}
