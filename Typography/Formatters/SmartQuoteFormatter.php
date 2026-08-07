<?php

namespace Kodhe\Typography\Formatters;

use Kodhe\Typography\Contracts\FormatterInterface;

/**
 * Formatter untuk smart quotes (tanda kutip cerdas).
 */
class SmartQuoteFormatter implements FormatterInterface
{
    /**
     * @var array Pola regex untuk tanda kutip
     */
    private array $patterns = [];

    /**
     * @var array ReplACEMENT untuk tanda kutip
     */
    private array $replacements = [];

    public function __construct()
    {
        $this->initializePatterns();
    }

    /**
     * Inisialisasi pola regex untuk smart quotes.
     */
    private function initializePatterns(): void
    {
        // Pola untuk double quotes
        $this->patterns[] = '/(\s|^)"([^"]*)"([^"\s]|$)/';
        $this->patterns[] = '/(\s|^)"([^"]*)$/';
        $this->patterns[] = '/^"([^"]*)"([^"\s]|$)/';
        
        // Pola untuk single quotes
        $this->patterns[] = "/(\s|^)'([^']*)'([^'\s]|$)/";
        $this->patterns[] = "/(\s|^)'([^']*)$/";
        $this->patterns[] = "/^'([^']*)'([^'\s]|$)/";

        $this->replacements = [
            '$1&ldquo;$2&rdquo;$3',
            '$1&ldquo;$2&rdquo;',
            '&ldquo;$1&rdquo;$2',
            '$1&lsquo;$2&rsquo;$3',
            '$1&lsquo;$2&rsquo;',
            '&lsquo;$1&rsquo;$2'
        ];
    }

    /**
     * Format teks dengan smart quotes.
     */
    public function format(string $text): string
    {
        return preg_replace($this->patterns, $this->replacements, $text);
    }
}
