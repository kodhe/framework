<?php

namespace Kodhe\Driver\Resolvers;

/**
 * Class DriverFileResolver
 *
 * Resolver untuk mencari dan loading file driver dari folder drivers/.
 * Mendukung pencarian di APPPATH dan BASEPATH.
 *
 * @package     Kodhe\Driver\Resolvers
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class DriverFileResolver
{
    /**
     * Cache path file yang sudah ditemukan
     *
     * @var array<string, string>
     */
    private static $pathCache = [];

    /**
     * Resolve nama class dari lib name dan driver name
     *
     * @param string $libName Nama library
     * @param string $driverName Nama driver
     * @return string Nama class driver
     */
    public function resolveClassName(string $libName, string $driverName): string
    {
        // Format: LibName_drivername atau LibName_libname_drivername
        return $libName . '_' . $driverName;
    }

    /**
     * Load file driver berdasarkan lib name dan driver name
     *
     * @param string $libName Nama library
     * @param string $driverName Nama driver
     * @return void
     * @throws \RuntimeException Jika file driver tidak ditemukan
     */
    public function loadFile(string $libName, string $driverName): void
    {
        $className = $this->resolveClassName($libName, $driverName);

        // Cek apakah class sudah pernah di-load (tanpa trigger autoloader)
        if (class_exists($className, false)) {
            return;
        }

        // Cek cache path dulu
        $cacheKey = $libName . ':' . $driverName;
        if (isset(self::$pathCache[$cacheKey])) {
            require_once self::$pathCache[$cacheKey];
            return;
        }

        // Cari file di berbagai lokasi
        $paths = [
            APPPATH,
            BASEPATH,
        ];

        foreach ($paths as $basePath) {
            // Coba format: libraries/LibName/drivers/ClassName.php
            $file = $basePath . 'libraries/' . $libName . '/drivers/' . $className . '.php';

            if (file_exists($file)) {
                self::$pathCache[$cacheKey] = $file;
                require_once $file;
                return;
            }

            // Coba format lowercase: libraries/libname/drivers/ClassName.php
            $fileLower = $basePath . 'libraries/' . strtolower($libName) . '/drivers/' . $className . '.php';

            if (file_exists($fileLower)) {
                self::$pathCache[$cacheKey] = $fileLower;
                require_once $fileLower;
                return;
            }
        }

        throw new \RuntimeException(
            "Unable to load the requested driver: {$className}"
        );
    }

    /**
     * Clear path cache
     * Berguna untuk testing atau reload driver
     *
     * @return void
     */
    public function clearCache(): void
    {
        self::$pathCache = [];
    }

    /**
     * Get path cache (untuk testing)
     *
     * @return array<string, string>
     */
    public function getPathCache(): array
    {
        return self::$pathCache;
    }
}
