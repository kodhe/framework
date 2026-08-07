<?php

namespace Kodhe\Typography\Formatters;

use Kodhe\Typography\Contracts\FormatterInterface;

/**
 * Formatter untuk paragraf dan line breaks.
 */
class ParagraphFormatter implements FormatterInterface
{
    /**
     * @var bool Apakah mengurangi line breaks
     */
    private bool $reduceLinebreaks;

    /**
     * Constructor.
     *
     * @param bool $reduceLinebreaks
     */
    public function __construct(bool $reduceLinebreaks = false)
    {
        $this->reduceLinebreaks = $reduceLinebreaks;
    }

    /**
     * Format teks dengan menambahkan tag <p> dan <br>.
     */
    public function format(string $text): string
    {
        if ($this->reduceLinebreaks) {
            return $this->formatWithReducedBreaks($text);
        }

        return $this->formatWithNormalBreaks($text);
    }

    /**
     * Format dengan line breaks normal.
     */
    private function formatWithNormalBreaks(string $text): string
    {
        // Ubah double newline menjadi paragraf
        $text = preg_replace("/(\n\n|\r\r)/", "</p><p>", $text);
        
        // Ubah single newline menjadi <br>
        $text = preg_replace("/(\n|\r)/", "<br>", $text);
        
        // Bungkus dengan <p> jika belum ada
        if (!preg_match('/^<p>/', $text)) {
            $text = '<p>' . $text;
        }
        
        if (!preg_match('/<\/p>$/', $text)) {
            $text .= '</p>';
        }

        return trim($text);
    }

    /**
     * Format dengan mengurangi line breaks.
     */
    private function formatWithReducedBreaks(string $text): string
    {
        // Kurangi multiple newlines menjadi satu
        $text = preg_replace("/[\r\n]+/", "\n", $text);
        
        // Ubah double newline menjadi paragraf
        $text = preg_replace("/\n\n/", "</p><p>", $text);
        
        // Bungkus dengan <p> jika belum ada
        if (!preg_match('/^<p>/', $text)) {
            $text = '<p>' . $text;
        }
        
        if (!preg_match('/<\/p>$/', $text)) {
            $text .= '</p>';
        }

        return trim($text);
    }
}
