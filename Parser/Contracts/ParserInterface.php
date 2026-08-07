<?php
/**
 * Parser Interface
 *
 * @package CodeIgniter\Parser\Contracts
 */

namespace CodeIgniter\Parser\Contracts;

interface ParserInterface
{
    /**
     * Parse a template string
     *
     * @param string $template
     * @param array  $data
     * @param bool   $return
     * @return string
     */
    public function parse(string $template, array $data = [], bool $return = false): string;

    /**
     * Parse a view file
     *
     * @param string $view
     * @param array  $data
     * @param bool   $return
     * @return string
     */
    public function parse_string(string $view, array $data = [], bool $return = false): string;

    /**
     * Set delimiters
     *
     * @param string $l
     * @param string $r
     * @return self
     */
    public function set_delimiters(string $l = '{', string $r = '}'): self;
}
