<?php

namespace Kodhe\Migration\Support;

/**
 * Helper untuk operasi migration
 *
 * @package Kodhe\Migration\Support
 */
class MigrationHelper
{
    /**
     * Get list file migration dari folder
     *
     * @param string $path
     * @return array
     */
    public static function getMigrationFiles(string $path): array
    {
        $files = [];
        
        if (!is_dir($path)) {
            return $files;
        }

        $dirIterator = new \DirectoryIterator($path);

        foreach ($dirIterator as $fileInfo) {
            if ($fileInfo->isFile() && $fileInfo->getExtension() === 'php') {
                $files[] = $fileInfo->getFilename();
            }
        }

        sort($files);
        return $files;
    }

    /**
     * Extract version dari filename migration
     *
     * @param string $filename
     * @return int|null
     */
    public static function getVersionFromFile(string $filename): ?int
    {
        if (preg_match('/^(\d+)_.*\.php$/', $filename, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }

    /**
     * Check apakah filename valid untuk migration
     *
     * @param string $filename
     * @return bool
     */
    public static function isValidMigrationFile(string $filename): bool
    {
        return (bool) preg_match('/^\d+_.+\.php$/', $filename);
    }

    /**
     * Get class name dari filename migration
     *
     * @param string $filename
     * @return string
     */
    public static function getClassName(string $filename): string
    {
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        return 'Migration_' . $basename;
    }

    /**
     * Normalize path migration
     *
     * @param string $path
     * @return string
     */
    public static function normalizePath(string $path): string
    {
        return rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
}
