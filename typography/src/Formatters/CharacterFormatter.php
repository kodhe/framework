<?php

declare(strict_types=1);

namespace Kodhe\Typography\Formatters;

use Kodhe\Typography\Contracts\FormatterInterface;
use Kodhe\Typography\Support\RegexCache;

/**
 * Character Formatter
 * 
 * Formats typographic characters (quotes, dashes, ellipsis, etc.)
 */
class CharacterFormatter implements FormatterInterface
{
    /**
     * @var array Character replacement table (cached)
     */
    private static $characterTable;

    /**
     * Format characters in the text.
     *
     * @param string $text
     * @return string
     */
    public function format(string $text): string
    {
        if (self::$characterTable === null) {
            $this->initializeCharacterTable();
        }

        return preg_replace(array_keys(self::$characterTable), self::$characterTable, $text);
    }

    /**
     * Initialize the character replacement table.
     *
     * @return void
     */
    private function initializeCharacterTable(): void
    {
        self::$characterTable = [
            // nested smart quotes, opening and closing
            '/\'"(\s|$)/'                    => '&#8217;&#8221;$1',
            '/(^|\s|<p>)\'"/'                => '$1&#8216;&#8220;',
            '/\'"(\W)/'                      => '&#8217;&#8221;$1',
            '/(\W)\'"/'                      => '$1&#8216;&#8220;',
            '/"\'(\s|$)/'                    => '&#8221;&#8217;$1',
            '/(^|\s|<p>)"\''/                => '$1&#8220;&#8216;',
            '/"\'(\W)/'                      => '&#8221;&#8217;$1',
            '/(\W)"\''/                      => '$1&#8220;&#8216;',

            // single quote smart quotes
            '/\'(\s|$)/'                     => '&#8217;$1',
            '/(^|\s|<p>)\'/'                 => '$1&#8216;',
            '/\'(\W)/'                       => '&#8217;$1',
            '/(\W)\'/'                       => '$1&#8216;',

            // double quote smart quotes
            '/"(\s|$)/'                      => '&#8221;$1',
            '/(^|\s|<p>)"'/                  => '$1&#8220;',
            '/"(\W)/'                        => '&#8221;$1',
            '/(\W)"/'                        => '$1&#8220;',

            // apostrophes
            "/(\w)'(\w)/"                    => '$1&#8217;$2',

            // Em dash and ellipses dots
            '/\s?--\s?/'                     => '&#8212;',
            '/(\w)\.{3}/'                    => '$1&#8230;',

            // double space after sentences
            '/(\W)  /'                       => '$1&nbsp; ',

            // ampersands, if not a character entity
            '/&(?!#?[a-zA-Z0-9]{2,};)/'      => '&amp;'
        ];
    }

    /**
     * Clear the cached character table.
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$characterTable = null;
    }
}
