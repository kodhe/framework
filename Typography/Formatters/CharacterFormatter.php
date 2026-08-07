<?php

namespace Kodhe\Typography\Formatters;

use Kodhe\Typography\Contracts\FormatterInterface;

/**
 * Formatter untuk karakter khusus (em-dash, ellipsis, dll).
 */
class CharacterFormatter implements FormatterInterface
{
    /**
     * @var array Mapping karakter khusus
     */
    private array $charMap = [
        '--'      => '&#8212;',
        '---'     => '&#8212;',
        '(c)'     => '&#169;',
        '(C)'     => '&#169;',
        '(r)'     => '&#174;',
        '(R)'     => '&#174;',
        '(tm)'    => '&#8482;',
        '(Tm)'    => '&#8482;',
        '(TM)'    => '&#8482;',
        '..'      => '&#8230;',
        '...'     => '&#8230;',
        '::'      => '&#8757;',
        "'s"      => '&rsquo;s',
        "'S"      => '&rsquo;s',
        "''"      => '&rdquo;',
    ];

    /**
     * Format karakter khusus.
     */
    public function format(string $text): string
    {
        foreach ($this->charMap as $search => $replace) {
            $text = str_replace($search, $replace, $text);
        }

        return $text;
    }
}
