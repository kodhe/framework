<?php

declare(strict_types=1);

namespace Kodhe\Framework\Support;

/**
 * Atomic, non-executable cache file writer/reader.
 *
 * Cache payloads (modules, routes, ...) used to be stored as PHP files
 * wrapping a JSON heredoc which were then parsed back with a regex.
 * That had two problems:
 *
 *  1. Storing generated content inside `.php` files is a code-execution
 *     vector whenever the storage directory is web-served or the file is
 *     ever `include`d by current or future code.
 *  2. The regex parser broke on CRLF line endings (Windows-generated
 *     files), silently invalidating the cache.
 *
 * This helper stores pure JSON in `.json` files and writes them
 * atomically (tempnam + rename) so concurrent readers never observe a
 * partially written file. On read it transparently migrates legacy
 * `.cache.php` heredoc files (CRLF-tolerant) to the new format.
 */
class CacheFileWriter
{
    /**
     * Encode data and write it atomically to $file.
     *
     * @throws \RuntimeException on encode or write failure.
     */
    public static function write(string $file, array $data): void
    {
        $dir = dirname($file);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException("Failed to create cache directory: {$dir}");
        }

        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($json === false) {
            throw new \RuntimeException('Failed to encode cache data to JSON: ' . json_last_error_msg());
        }

        // Write to a temp file in the same directory, then rename() over
        // the target. rename() is atomic on POSIX and on Windows (since
        // PHP 7.x it replaces existing files), so readers either see the
        // old complete file or the new complete file - never a partial one.
        $tmp = @tempnam($dir, '.cache~');
        if ($tmp === false) {
            throw new \RuntimeException("Failed to create temporary cache file in: {$dir}");
        }

        if (@file_put_contents($tmp, $json . "\n") === false) {
            @unlink($tmp);
            throw new \RuntimeException("Failed to write temporary cache file: {$tmp}");
        }

        if (!@rename($tmp, $file)) {
            @unlink($tmp);
            throw new \RuntimeException("Failed to replace cache file: {$file}");
        }

        @chmod($file, 0644);
    }

    /**
     * Read and decode a JSON cache file.
     *
     * Returns null when the file is missing, unreadable, not valid JSON,
     * or does not contain the expected top-level $requiredKey.
     */
    public static function read(string $file, string $requiredKey): ?array
    {
        if (!is_file($file)) {
            return null;
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        if (!is_array($data) || !isset($data[$requiredKey])) {
            return null;
        }

        return $data;
    }

    /**
     * Derive the legacy PHP-heredoc cache path for a given .json cache path.
     */
    public static function legacyPath(string $file): string
    {
        return preg_replace('/\.json$/', '.php', $file) ?? ($file . '.legacy.php');
    }

    /**
     * Read a legacy `.cache.php` heredoc file (CRLF-tolerant).
     *
     * Returns the decoded payload, or null when the file is missing or
     * cannot be parsed. Never executes the file's contents.
     */
    public static function readLegacy(string $legacyFile, string $requiredKey): ?array
    {
        if (!is_file($legacyFile)) {
            return null;
        }

        $content = @file_get_contents($legacyFile);
        if ($content === false) {
            return null;
        }

        // Primary extraction: the heredoc body. \R? handles LF and CRLF.
        if (preg_match("/return <<<'CACHE'\r?\n(.*?)\r?\nCACHE;/s", $content, $matches)) {
            $jsonData = $matches[1];
        } else {
            // Fallback for hand-edited/broken files: strip comments and
            // the php tag, keep only the outermost JSON object/array.
            $stripped = preg_replace('#^\s*<\?php#', '', $content);
            $stripped = preg_replace('#^//.*$#m', '', (string) $stripped);
            $stripped = trim((string) $stripped);

            if (preg_match('/[\{\[].*[\}\]]/s', $stripped, $matches)) {
                $jsonData = $matches[0];
            } else {
                return null;
            }
        }

        $data = json_decode($jsonData, true);
        if (!is_array($data) || !isset($data[$requiredKey])) {
            return null;
        }

        return $data;
    }
}
