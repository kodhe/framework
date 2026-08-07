<?php

namespace Kodhe\Typography\Factory;

use Kodhe\Typography\Typography;
use Kodhe\Typography\Contracts\FormatterInterface;
use Kodhe\Typography\Contracts\ParserInterface;
use Kodhe\Typography\Formatters\SmartQuoteFormatter;
use Kodhe\Typography\Formatters\CharacterFormatter;
use Kodhe\Typography\Formatters\ParagraphFormatter;
use Kodhe\Typography\Parsers\HtmlParser;
use Kodhe\Typography\Support\TagProtector;
use Kodhe\Typography\ValueObjects\TypographyConfig;

/**
 * Factory untuk membuat instance Typography.
 */
class TypographyFactory
{
    /**
     * Buat instance Typography dengan konfigurasi default.
     */
    public static function make(): Typography
    {
        return new Typography(
            new HtmlParser(),
            new SmartQuoteFormatter(),
            new CharacterFormatter(),
            new ParagraphFormatter(),
            new TagProtector()
        );
    }

    /**
     * Buat instance Typography dengan konfigurasi kustom.
     *
     * @param array $config
     * @return Typography
     */
    public static function makeWithConfig(array $config = []): Typography
    {
        $typographyConfig = TypographyConfig::fromArray($config);

        return new Typography(
            new HtmlParser(),
            new SmartQuoteFormatter(),
            new CharacterFormatter(),
            new ParagraphFormatter($typographyConfig->shouldReduceLinebreaks()),
            new TagProtector(),
            $typographyConfig
        );
    }

    /**
     * Buat instance Typography dengan komponen kustom (untuk testing/DI).
     *
     * @param ParserInterface $parser
     * @param FormatterInterface $quoteFormatter
     * @param FormatterInterface $charFormatter
     * @param FormatterInterface $paragraphFormatter
     * @param TagProtector $tagProtector
     * @param TypographyConfig|null $config
     * @return Typography
     */
    public static function makeWithComponents(
        ParserInterface $parser,
        FormatterInterface $quoteFormatter,
        FormatterInterface $charFormatter,
        FormatterInterface $paragraphFormatter,
        TagProtector $tagProtector,
        ?TypographyConfig $config = null
    ): Typography {
        return new Typography(
            $parser,
            $quoteFormatter,
            $charFormatter,
            $paragraphFormatter,
            $tagProtector,
            $config ?? new TypographyConfig()
        );
    }
}
