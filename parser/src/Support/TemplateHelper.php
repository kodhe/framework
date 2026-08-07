<?php

declare(strict_types=1);

namespace Kodhe\Parser\Support;

/**
 * Template Helper
 *
 * Utility functions for template parsing.
 */
class TemplateHelper
{
    /**
     * Extract tag pair pattern for regex matching
     */
    public static function getTagPairPattern(string $variable, string $lDelim, string $rDelim): string
    {
        $open = preg_quote($lDelim . $variable . $rDelim);
        $close = preg_quote($lDelim . '/' . $variable . $rDelim);
        
        return '#' . $open . '(.+?)' . $close . '#s';
    }

    /**
     * Generate cache key for template
     */
    public static function generateCacheKey(string $template, array $data, string $lDelim, string $rDelim): string
    {
        return hash('sha256', $template . serialize($data) . $lDelim . $rDelim);
    }

    /**
     * Check if value is associative array
     */
    public static function isAssociativeArray(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }
        
        if ($value === []) {
            return false;
        }
        
        return array_keys($value) !== range(0, count($value) - 1);
    }

    /**
     * Escape special characters for replacement
     */
    public static function escapeReplacement(string $value): string
    {
        return str_replace(
            ['\\', '$'],
            ['\\\\', '\\$'],
            $value
        );
    }

    /**
     * Normalize delimiters
     */
    public static function normalizeDelimiters(string &$l, string &$r): void
    {
        $l = $l ?: '{';
        $r = $r ?: '}';
    }
}
