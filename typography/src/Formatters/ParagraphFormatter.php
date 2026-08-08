<?php

declare(strict_types=0);

namespace Kodhe\Framework\Typography\Formatters;

use Kodhe\Framework\Typography\Contracts\FormatterInterface;
use Kodhe\Framework\Typography\Support\RegexCache;

/**
 * Paragraph Formatter
 * 
 * Formats text into paragraphs and line breaks.
 */
class ParagraphFormatter implements FormatterInterface
{
    /**
     * @var string Last block element processed
     */
    private $lastBlockElement = '';

    /**
     * @var array Inner block required elements
     */
    private $innerBlockRequired = ['blockquote'];

    /**
     * Format text into paragraphs.
     *
     * @param string $text
     * @return string
     */
    public function format(string $text): string
    {
        if ($text === '' || (strpos($text, "\n") === false && !in_array($this->lastBlockElement, $this->innerBlockRequired))) {
            return $text;
        }

        // Convert two consecutive newlines to paragraphs
        $text = str_replace("\n\n", "</p>\n\n<p>", $text);

        // Convert single spaces to <br /> tags
        $text = preg_replace("/([^\n])(\n)([^\n])/", '\\1<br />\\2\\3', $text);

        // Wrap the whole enchilada in enclosing paragraphs
        if ($text !== "\n") {
            $text = '<p>' . rtrim($text) . '</p>';
        }

        // Remove empty paragraphs if they are on the first line
        return preg_replace('/<p><\/p>(.*)/', '\\1', $text, 1);
    }

    /**
     * Set the last block element.
     *
     * @param string $element
     * @return void
     */
    public function setLastBlockElement(string $element): void
    {
        $this->lastBlockElement = $element;
    }

    /**
     * Get the last block element.
     *
     * @return string
     */
    public function getLastBlockElement(): string
    {
        return $this->lastBlockElement;
    }

    /**
     * Set inner block required elements.
     *
     * @param array $elements
     * @return void
     */
    public function setInnerBlockRequired(array $elements): void
    {
        $this->innerBlockRequired = $elements;
    }
}
