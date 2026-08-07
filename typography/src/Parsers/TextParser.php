<?php

declare(strict_types=1);

namespace Kodhe\Typography\Parsers;

/**
 * Text Parser
 * 
 * Simple text parser for basic typography operations.
 */
class TextParser
{
    /**
     * Convert newlines to HTML line breaks except within PRE tags.
     *
     * @param string $text
     * @return string
     */
    public function nl2brExceptPre(string $text): string
    {
        $newstr = '';
        $ex = explode('pre>', $text);
        $ct = count($ex);

        for ($i = 0; $i < $ct; $i++) {
            $newstr .= (($i % 2) === 0) ? nl2br($ex[$i]) : $ex[$i];
            if ($ct - 1 !== $i) {
                $newstr .= 'pre>';
            }
        }

        return $newstr;
    }

    /**
     * Protect braced quotes in text.
     *
     * @param string $text
     * @return string
     */
    public function protectBracedQuotes(string $text): string
    {
        // Match content within curly braces and protect quotes
        return preg_replace_callback('#\{(.+?)\}#s', function ($matches) {
            $content = $matches[1];
            // Protect quotes within braces
            $protected = str_replace(
                ["'", '"'],
                ['&#8216;', '&#8220;'],
                $content
            );
            return '{' . $protected . '}';
        }, $text);
    }
}
