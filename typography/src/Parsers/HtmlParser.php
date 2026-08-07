<?php

declare(strict_types=1);

namespace Kodhe\Typography\Parsers;

use Kodhe\Typography\Contracts\ParserInterface;
use Kodhe\Typography\Contracts\FormatterInterface;
use Kodhe\Typography\Support\HtmlProtect;
use Kodhe\Typography\Support\RegexCache;
use Kodhe\Typography\Formatters\ParagraphFormatter;
use Kodhe\Typography\Formatters\CharacterFormatter;
use Kodhe\Typography\Exceptions/TypographyException;

/**
 * HTML Parser
 * 
 * Parses HTML content for typography processing.
 */
class HtmlParser implements ParserInterface
{
    /**
     * @var HtmlProtect Protector instance
     */
    private $htmlProtect;

    /**
     * @var string Block elements pattern
     */
    private $blockElementsPattern;

    /**
     * @var string Skip elements pattern
     */
    private $skipElementsPattern;

    /**
     * @var string Inline elements pattern
     */
    private $inlineElementsPattern;

    /**
     * @var bool Process flag
     */
    private $process = true;

    /**
     * @var string Last block element
     */
    private $lastBlockElement = '';

    /**
     * @var array Inner block required
     */
    private $innerBlockRequired = [];

    /**
     * Constructor.
     *
     * @param HtmlProtect|null $htmlProtect
     */
    public function __construct(?HtmlProtect $htmlProtect = null)
    {
        $this->htmlProtect = $htmlProtect ?? new HtmlProtect();
    }

    /**
     * Parse the text.
     *
     * @param string $text
     * @param array $config
     * @return string
     */
    public function parse(string $text, array $config = []): string
    {
        $this->configure($config);

        if ($text === '') {
            return '';
        }

        // Standardize Newlines
        if (strpos($text, "\r") !== false) {
            $text = str_replace(["\r\n", "\r"], "\n", $text);
        }

        // Extract HTML comments
        $htmlComments = [];
        if (strpos($text, '<!--') !== false && preg_match_all('#(<!\-\-.*?\-\->)#s', $text, $matches)) {
            for ($i = 0, $total = count($matches[0]); $i < $total; $i++) {
                $htmlComments[] = $matches[0][$i];
                $text = str_replace($matches[0][$i], '{@HC' . $i . '}', $text);
            }
        }

        // Protect <pre> tags
        if (strpos($text, '<pre') !== false) {
            $text = preg_replace_callback('#<pre.*?>.*?</pre>#si', [$this, 'protectCharacters'], $text);
        }

        // Protect quotes within tags
        $text = preg_replace_callback('#<.+?>#si', [$this, 'protectCharacters'], $text);

        // Protect braces if configured
        if ($config['protect_braced_quotes'] ?? false) {
            $text = preg_replace_callback('#\{.+?\}#si', [$this, 'protectCharacters'], $text);
        }

        // Convert inline tags to temporary markers
        $text = preg_replace('#<(/*)(' . $this->inlineElementsPattern . ')([ >])#i', '{@TAG}\\1\\2\\3', $text);

        // Split at every tag
        $chunks = preg_split('/(<(?:[^<>]+(?:"[^"]*"|\'[^\']*\')?)+>)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        // Process chunks
        $result = '';
        $chunkCount = count($chunks) - 1;

        for ($i = 0; $i <= $chunkCount; $i++) {
            if (preg_match('#<(/*)(' . $this->blockElementsPattern . ').*?>#', $chunks[$i], $match)) {
                if (preg_match('#' . $this->skipElementsPattern . '#', $match[2])) {
                    $this->process = ($match[1] === '/');
                }

                if ($match[1] === '') {
                    $this->lastBlockElement = $match[2];
                }

                $result .= $chunks[$i];
                continue;
            }

            if ($this->process === false) {
                $result .= $chunks[$i];
                continue;
            }

            // Force newline at end
            if ($i === $chunkCount) {
                $chunks[$i] .= "\n";
            }

            // Format newlines
            $result .= $this->formatNewlines($chunks[$i]);
        }

        // Add opening paragraph if needed
        if (!preg_match('/^\s*<(?:' . $this->blockElementsPattern . ')/i', $result)) {
            $result = preg_replace('/^(.*?)<(' . $this->blockElementsPattern . ')/i', '<p>$1</p><$2', $result);
        }

        // Restore HTML comments
        foreach ($htmlComments as $i => $comment) {
            $pattern = '#(?(?=<p>\{@HC' . $i . '\})<p>\{@HC' . $i . '\}(\s*</p>)|\{@HC' . $i . '\})#s';
            $result = preg_replace($pattern, $comment, $result);
        }

        return $result;
    }

    /**
     * Configure parser with settings.
     *
     * @param array $config
     * @return void
     */
    private function configure(array $config): void
    {
        $this->blockElementsPattern = $config['block_elements'] ?? 'address|blockquote|div|dl|fieldset|form|h\d|hr|noscript|object|ol|p|pre|script|table|ul';
        $this->skipElementsPattern = $config['skip_elements'] ?? 'p|pre|ol|ul|dl|object|table|h\d';
        $this->inlineElementsPattern = $config['inline_elements'] ?? 'a|abbr|acronym|b|bdo|big|br|button|cite|code|del|dfn|em|i|img|ins|input|label|map|kbd|q|samp|select|small|span|strong|sub|sup|textarea|tt|var';
        $this->innerBlockRequired = $config['inner_block_required'] ?? ['blockquote'];
    }

    /**
     * Format newlines in text.
     *
     * @param string $text
     * @return string
     */
    private function formatNewlines(string $text): string
    {
        $formatter = new ParagraphFormatter();
        $formatter->setLastBlockElement($this->lastBlockElement);
        $formatter->setInnerBlockRequired($this->innerBlockRequired);
        return $formatter->format($text);
    }

    /**
     * Protect characters callback.
     *
     * @param array $match
     * @return string
     */
    private function protectCharacters(array $match): string
    {
        return str_replace(["'", '"', '--', '  '], ['{@SQ}', '{@DQ}', '{@DD}', '{@NBS}'], $match[0]);
    }

    /**
     * Get last block element.
     *
     * @return string
     */
    public function getLastBlockElement(): string
    {
        return $this->lastBlockElement;
    }
}
