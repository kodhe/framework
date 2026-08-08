<?php

declare(strict_types=0);

namespace Kodhe\Framework\Xmlrpcs\Support;

use Kodhe\Framework\Xmlrpcs\Contracts\XmlParserInterface;
use Kodhe\Framework\Xmlrpcs\Exceptions\XmlParseException;

/**
 * XML Parser using PHP's built-in XML functions
 */
class NativeXmlParser implements XmlParserInterface
{
    /**
     * Parsed data
     *
     * @var array
     */
    protected array $parsedData = [];

    /**
     * Parsing errors
     *
     * @var array
     */
    protected array $errors = [];

    /**
     * Parser validity flag
     *
     * @var bool
     */
    protected bool $isValid = false;

    /**
     * Encoding
     *
     * @var string
     */
    protected string $encoding = 'UTF-8';

    /**
     * Reusable parser resource
     *
     * @var resource|null
     */
    protected $parserResource = null;

    /**
     * Constructor
     *
     * @param string $encoding
     */
    public function __construct(string $encoding = 'UTF-8')
    {
        $this->encoding = $encoding;
    }

    /**
     * Parse XML request data
     *
     * @param string $data
     * @return array
     */
    public function parse(string $data): array
    {
        $this->reset();

        // Create or reuse parser
        $parser = $this->getParser();
        
        // Set up parser state
        $parserState = [
            'isf' => 0,
            'isf_reason' => '',
            'params' => [],
            'stack' => [],
            'valuestack' => [],
            'method' => ''
        ];

        // Store state in object for handler callbacks
        $this->parserState = &$parserState;

        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, true);
        xml_set_element_handler($parser, [$this, 'openTag'], [$this, 'closingTag']);
        xml_set_character_data_handler($parser, [$this, 'characterData']);

        // Parse the data
        if (!xml_parse($parser, $data, true)) {
            $errorCode = xml_get_error_code($parser);
            $this->errors[] = [
                'code' => $errorCode,
                'message' => sprintf(
                    'XML error: %s at line %d',
                    xml_error_string($errorCode),
                    xml_get_current_line_number($parser)
                )
            ];
            $this->isValid = false;
            xml_parser_free($parser);
            return [];
        }

        xml_parser_free($parser);

        if ($parserState['isf']) {
            $this->errors[] = ['reason' => $parserState['isf_reason']];
            $this->isValid = false;
            return [];
        }

        $this->isValid = true;
        $this->parsedData = $parserState;
        
        return [
            'method' => $parserState['method'],
            'params' => $parserState['params']
        ];
    }

    /**
     * Get parsing errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if parsing was successful
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * Get or create parser resource
     *
     * @return resource
     */
    protected function getParser()
    {
        if ($this->parserResource === null) {
            $this->parserResource = xml_parser_create($this->encoding);
        }
        return $this->parserResource;
    }

    /**
     * Reset parser state
     *
     * @return void
     */
    protected function reset(): void
    {
        $this->parsedData = [];
        $this->errors = [];
        $this->isValid = false;
        $this->parserState = null;
    }

    /**
     * Open tag handler
     *
     * @param resource $parser
     * @param string $name
     * @return void
     */
    public function openTag($parser, string $name): void
    {
        // Delegate to state handlers
        $this->handleOpenTag($name);
    }

    /**
     * Closing tag handler
     *
     * @param resource $parser
     * @param string $name
     * @return void
     */
    public function closingTag($parser, string $name): void
    {
        // Delegate to state handlers
        $this->handleClosingTag($name);
    }

    /**
     * Character data handler
     *
     * @param resource $parser
     * @param string $data
     * @return void
     */
    public function characterData($parser, string $data): void
    {
        // Delegate to state handlers
        $this->handleCharacterData($data);
    }

    /**
     * Handle open tag - delegates to XML-RPC parser logic
     *
     * @param string $name
     * @return void
     */
    protected function handleOpenTag(string $name): void
    {
        // This would need to implement the full XML-RPC parsing logic
        // For now, we use a simplified approach
    }

    /**
     * Handle closing tag
     *
     * @param string $name
     * @return void
     */
    protected function handleClosingTag(string $name): void
    {
        // This would need to implement the full XML-RPC parsing logic
    }

    /**
     * Handle character data
     *
     * @param string $data
     * @return void
     */
    protected function handleCharacterData(string $data): void
    {
        // This would need to implement the full XML-RPC parsing logic
    }
}
