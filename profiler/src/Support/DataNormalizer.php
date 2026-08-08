<?php

declare(strict_types=0);

namespace Kodhe\Framework\Profiler\Support;

/**
 * Data Normalizer
 * 
 * Normalizes and sanitizes data for display
 */
class DataNormalizer
{
    private string $charset = 'UTF-8';

    public function __construct(string $charset = 'UTF-8')
    {
        $this->charset = $charset;
    }

    /**
     * Normalize a value for display
     * Handles arrays, objects, and scalars
     */
    public function normalize(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            return '<pre>' . $this->escape(print_r($value, true)) . '</pre>';
        }

        return $this->escape((string)$value);
    }

    /**
     * Escape HTML entities
     */
    public function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, $this->charset);
    }

    /**
     * Escape array keys for display
     */
    public function escapeKey(mixed $key): string
    {
        if (is_int($key)) {
            return (string)$key;
        }
        return "'" . $this->escape($key) . "'";
    }

    /**
     * Prepare data for table display
     */
    public function prepareForTable(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $result[$this->escapeKey($key)] = $this->normalize($value);
        }
        return $result;
    }

    /**
     * Highlight SQL keywords
     */
    public function highlightSql(string $sql): string
    {
        $highlight = [
            'SELECT', 'DISTINCT', 'FROM', 'WHERE', 'AND', 
            'LEFT&nbsp;JOIN', 'ORDER&nbsp;BY', 'GROUP&nbsp;BY', 
            'LIMIT', 'INSERT', 'INTO', 'VALUES', 'UPDATE', 
            'OR&nbsp;', 'HAVING', 'OFFSET', 'NOT&nbsp;IN', 
            'IN', 'LIKE', 'NOT&nbsp;LIKE', 'COUNT', 'MAX', 
            'MIN', 'ON', 'AS', 'AVG', 'SUM', '(', ')'
        ];

        $highlighted = highlight_code($sql);

        foreach ($highlight as $bold) {
            $highlighted = str_replace($bold, '<strong>' . $bold . '</strong>', $highlighted);
        }

        return $highlighted;
    }

    /**
     * Format memory usage
     */
    public function formatMemory(int $bytes): string
    {
        return number_format($bytes) . ' bytes';
    }

    /**
     * Format time duration
     */
    public function formatTime(float $seconds, int $decimals = 4): string
    {
        return number_format($seconds, $decimals);
    }
}
