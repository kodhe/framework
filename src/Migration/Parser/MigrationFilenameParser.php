<?php

namespace Kodhe\Migration\Parser;

use Kodhe\Migration\Exceptions\InvalidMigrationFileException;

/**
 * Parser untuk filename migration
 *
 * Format: {timestamp}_{description}.php atau {version}_{description}.php
 *
 * @package Kodhe\Migration\Parser
 */
class MigrationFilenameParser
{
    /**
     * Pattern untuk validasi filename migration
     */
    private const PATTERN = '/^(\d+)_(.+)\.php$/';

    /**
     * Parse filename migration dan extract version
     *
     * @param string $filename
     * @return array ['version' => int, 'name' => string]
     * @throws InvalidMigrationFileException
     */
    public function parse(string $filename): array
    {
        if (!preg_match(self::PATTERN, $filename, $matches)) {
            throw new InvalidMigrationFileException($filename);
        }

        return [
            'version' => (int) $matches[1],
            'name' => $matches[2],
        ];
    }

    /**
     * Get version dari filename
     *
     * @param string $filename
     * @return int|null
     */
    public function getVersion(string $filename): ?int
    {
        try {
            $parsed = $this->parse($filename);
            return $parsed['version'];
        } catch (InvalidMigrationFileException $e) {
            return null;
        }
    }

    /**
     * Check apakah filename valid untuk migration
     *
     * @param string $filename
     * @return bool
     */
    public function isValid(string $filename): bool
    {
        return (bool) preg_match(self::PATTERN, $filename);
    }
}
