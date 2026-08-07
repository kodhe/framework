<?php

namespace Kodhe\Typography;

use Kodhe\Typography\Contracts\FormatterInterface;
use Kodhe\Typography\Contracts\ParserInterface;
use Kodhe\Typography\Parsers\HtmlParser;
use Kodhe\Typography\Formatters\SmartQuoteFormatter;
use Kodhe\Typography\Formatters\CharacterFormatter;
use Kodhe\Typography\Formatters\ParagraphFormatter;
use Kodhe\Typography\Support\TagProtector;
use Kodhe\Typography\ValueObjects\TypographyConfig;

/**
 * Class Typography - Library untuk formatting teks HTML.
 * 
 * API kompatibel 100% dengan CodeIgniter 3.
 */
class Typography
{
    /**
     * @var ParserInterface
     */
    private ParserInterface $parser;

    /**
     * @var FormatterInterface
     */
    private FormatterInterface $quoteFormatter;

    /**
     * @var FormatterInterface
     */
    private FormatterInterface $charFormatter;

    /**
     * @var FormatterInterface
     */
    private FormatterInterface $paragraphFormatter;

    /**
     * @var TagProtector
     */
    private TagProtector $tagProtector;

    /**
     * @var TypographyConfig|null
     */
    private ?TypographyConfig $config;

    /**
     * @var string Delimiter kiri
     */
    protected string $lDelim = '{';

    /**
     * @var string Delimiter kanan
     */
    protected string $rDelim = '}';

    /**
     * Constructor.
     *
     * @param ParserInterface|null $parser
     * @param FormatterInterface|null $quoteFormatter
     * @param FormatterInterface|null $charFormatter
     * @param FormatterInterface|null $paragraphFormatter
     * @param TagProtector|null $tagProtector
     * @param TypographyConfig|null $config
     */
    public function __construct(
        ?ParserInterface $parser = null,
        ?FormatterInterface $quoteFormatter = null,
        ?FormatterInterface $charFormatter = null,
        ?FormatterInterface $paragraphFormatter = null,
        ?TagProtector $tagProtector = null,
        ?TypographyConfig $config = null
    ) {
        $this->parser = $parser ?? new HtmlParser();
        $this->quoteFormatter = $quoteFormatter ?? new SmartQuoteFormatter();
        $this->charFormatter = $charFormatter ?? new CharacterFormatter();
        $this->paragraphFormatter = $paragraphFormatter ?? new ParagraphFormatter();
        $this->tagProtector = $tagProtector ?? new TagProtector();
        $this->config = $config;
    }

    /**
     * Auto-format teks menjadi typography yang benar.
     *
     * @param string $str Teks input
     * @param bool $reduceLinebreaks Apakah mengurangi line breaks
     * @return string Teks yang sudah diformat
     */
    public function auto_typography(string $str, bool $reduceLinebreaks = false): string
    {
        if (trim($str) === '') {
            return $str;
        }

        // Lindungi tag HTML
        $parsed = $this->parser->parse($str);
        $content = $parsed['content'];

        // Format karakter khusus
        $content = $this->charFormatter->format($content);

        // Format smart quotes
        $content = $this->quoteFormatter->format($content);

        // Format paragraf
        if ($reduceLinebreaks) {
            $content = $this->paragraphFormatter->format($content);
        } else {
            $paraFormatter = new ParagraphFormatter(false);
            $content = $paraFormatter->format($content);
        }

        // Restore tag HTML
        $content = $this->parser->restore($content, $parsed);

        return $content;
    }

    /**
     * Format karakter khusus saja.
     *
     * @param string $str Teks input
     * @return string Teks yang sudah diformat
     */
    public function format_characters(string $str): string
    {
        return $this->charFormatter->format($str);
    }

    /**
     * Ubah newline menjadi <br> kecuali di dalam tag <pre>.
     *
     * @param string $str Teks input
     * @return string Teks dengan <br>
     */
    public function nl2br_except_pre(string $str): string
    {
        // Pisahkan konten <pre>
        $parts = preg_split('/(<pre.*?>.*?<\/pre>)/is', $str, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        $result = '';
        foreach ($parts as $part) {
            // Jika bukan tag <pre>, ubah newline ke <br>
            if (!preg_match('/^<pre.*?>/is', $part)) {
                $result .= nl2br($part);
            } else {
                $result .= $part;
            }
        }

        return $result;
    }

    /**
     * Lindungi tanda kutip dalam kurung kurawal.
     *
     * @param string $str Teks input
     * @param array $tempSwap Swap sementara
     * @return string Teks yang sudah dilindungi
     */
    public function protect_braced_quotes(string $str, array $tempSwap = []): string
    {
        $protected = $this->tagProtector->protectBracedQuotes($str, $tempSwap);
        return $this->tagProtector->restoreBracedQuotes($protected['content'], $protected['protected']);
    }

    /**
     * Set delimiter untuk template parsing.
     *
     * @param string $l Left delimiter
     * @param string $r Right delimiter
     */
    public function set_delimiters(string $l = '{', string $r = '}'): void
    {
        $this->lDelim = $l;
        $this->rDelim = $r;
    }

    /**
     * Dapatkan left delimiter.
     *
     * @return string
     */
    public function get_left_delimiter(): string
    {
        return $this->lDelim;
    }

    /**
     * Dapatkan right delimiter.
     *
     * @return string
     */
    public function get_right_delimiter(): string
    {
        return $this->rDelim;
    }
}
