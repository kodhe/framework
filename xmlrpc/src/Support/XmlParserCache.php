<?php

declare(strict_types=0);

namespace Kodhe\Framework\Xmlrpc\Support;

/**
 * XML Parser cache for performance optimization
 */
class XmlParserCache
{
    /**
     * @var array
     */
    private static $parsers = [];

    /**
     * Get a cached XML parser or create a new one
     *
     * @param string $encoding
     * @return resource
     */
    public static function getParser(string $encoding = 'UTF-8')
    {
        $key = $encoding;

        if (!isset(self::$parsers[$key])) {
            self::$parsers[$key] = xml_parser_create($encoding);
        }

        return self::$parsers[$key];
    }

    /**
     * Reset parser state for reuse
     *
     * @param resource $parser
     * @return void
     */
    public static function resetParser($parser): void
    {
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, true);
    }

    /**
     * Clear all cached parsers
     *
     * @return void
     */
    public static function clear(): void
    {
        foreach (self::$parsers as $parser) {
            xml_parser_free($parser);
        }
        self::$parsers = [];
    }

    /**
     * Destructor - cleanup
     */
    public function __destruct()
    {
        self::clear();
    }
}
