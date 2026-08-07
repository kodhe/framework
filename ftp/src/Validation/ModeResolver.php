<?php

namespace Kodhe\Ftp\Validation;

/**
 * Class ModeResolver
 *
 * Menentukan mode transfer FTP (ascii atau binary) berdasarkan ekstensi file
 *
 * @package     Kodhe\Ftp\Validation
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class ModeResolver
{
    /**
     * Daftar ekstensi file yang menggunakan mode ASCII
     *
     * @var array<string>
     */
    private static array $asciiExtensions = [
        'txt', 'text', 'php', 'php3', 'php4', 'php5', 'phtml',
        'html', 'htm', 'xhtml', 'css', 'js', 'json',
        'xml', 'svg', 'ini', 'conf', 'cfg',
        'log', 'sql', 'sh', 'bat', 'cmd',
        'md', 'markdown', 'rst', 'csv', 'tsv',
        'yaml', 'yml', 'htaccess', 'htpasswd'
    ];

    /**
     * Cache untuk lookup ekstensi
     *
     * @var array<string, string>
     */
    private static array $cache = [];

    /**
     * Resolve mode transfer berdasarkan nama file
     *
     * @param string $filename Nama file (dengan path)
     * @return string 'ascii' atau 'binary'
     */
    public function resolve(string $filename): string
    {
        // Cek cache dulu
        if (isset(self::$cache[$filename])) {
            return self::$cache[$filename];
        }

        // Ekstrak ekstensi file
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Tentukan mode berdasarkan ekstensi
        $mode = in_array($extension, self::$asciiExtensions, true) ? 'ascii' : 'binary';

        // Simpan ke cache
        self::$cache[$filename] = $mode;

        return $mode;
    }

    /**
     * Tambahkan ekstensi custom ke daftar ASCII
     *
     * @param array<string> $extensions Daftar ekstensi baru
     * @return void
     */
    public function addAsciiExtensions(array $extensions): void
    {
        self::$asciiExtensions = array_merge(self::$asciiExtensions, $extensions);
        self::$cache = []; // Reset cache
    }

    /**
     * Dapatkan daftar ekstensi ASCII
     *
     * @return array<string>
     */
    public function getAsciiExtensions(): array
    {
        return self::$asciiExtensions;
    }

    /**
     * Clear cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        self::$cache = [];
    }
}
