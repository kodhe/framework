<?php

declare(strict_types=1);

namespace Kodhe\Parser\Contracts;

/**
 * Parser Interface
 *
 * Defines the contract for template parsers.
 * Compatible with CodeIgniter 3 Parser API.
 */
interface ParserInterface
{
    /**
     * Parse a template file
     *
     * @param string $template Template file path or view name
     * @param array $data Associative array of data to parse
     * @param bool $return Whether to return the parsed template or output it
     * @return string|bool Parsed template string or FALSE on failure
     */
    public function parse(string $template, array $data, bool $return = false);

    /**
     * Parse a string template
     *
     * @param string $template Template string to parse
     * @param array $data Associative array of data to parse
     * @param bool $return Whether to return or output
     * @return string|bool Parsed string or FALSE on failure
     */
    public function parse_string(string $template, array $data, bool $return = false);

    /**
     * Set the left/right variable delimiters
     *
     * @param string $l Left delimiter
     * @param string $r Right delimiter
     * @return void
     */
    public function set_delimiters(string $l, string $r): void;
}
