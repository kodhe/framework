<?php

declare(strict_types=1);

namespace Kodhe\Parser;

/**
 * Parser Class
 *
 * A simple template parser that replaces pseudo-variables in templates
 * with actual data. Supports single variables and tag pairs for looping.
 *
 * @package     CodeIgniter
 * @subpackage  Libraries
 * @category    Parser
 * @author      EllisLab Dev Team
 * @link        https://codeigniter.com/user_guide/libraries/parser.html
 *
 * @example
 * ```php
 * $parser = new Parser();
 *
 * // Simple variable replacement
 * $template = "Hello, {name}!";
 * echo $parser->parse_string($template, ['name' => 'World']);
 * // Output: Hello, World!
 *
 * // Tag pair (loop)
 * $template = "<ul>{items}<li>{item}</li>{/items}</ul>";
 * echo $parser->parse_string($template, [
 *     'items' => [
 *         ['item' => 'First'],
 *         ['item' => 'Second'],
 *         ['item' => 'Third']
 *     ]
 * ]);
 * // Output: <ul><li>First</li><li>Second</li><li>Third</li></ul>
 * ```
 */
class Parser
{
    /**
     * Left delimiter character for pseudo vars
     *
     * @var string
     */
    public $l_delim = '{';

    /**
     * Right delimiter character for pseudo vars
     *
     * @var string
     */
    public $r_delim = '}';

    /**
     * Reference to CodeIgniter instance
     *
     * @var object
     */
    protected $CI;

    /**
     * Class constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->CI = kodhe();
        log_message('info', 'Parser Class Initialized');
    }

    /**
     * Parse a template file
     *
     * Parses pseudo-variables contained in the specified template view,
     * replacing them with the data in the second param
     *
     * @param string $template Template file path or view name
     * @param array $data Associative array of data to parse
     * @param bool $return Whether to return the parsed template or output it
     * @return string|bool Parsed template string or FALSE on failure
     */
    public function parse($template, $data, $return = false)
    {
        $template = $this->CI->load->view($template, $data, true);

        return $this->_parse($template, $data, $return);
    }

    /**
     * Parse a String
     *
     * Parses pseudo-variables contained in the specified string,
     * replacing them with the data in the second param
     *
     * @param string $template Template string to parse
     * @param array $data Associative array of data to parse
     * @param bool $return Whether to return or output
     * @return string|bool Parsed string or FALSE on failure
     */
    public function parse_string($template, $data, $return = false)
    {
        return $this->_parse($template, $data, $return);
    }

    /**
     * Set the left/right variable delimiters
     *
     * @param string $l Left delimiter (default: '{')
     * @param string $r Right delimiter (default: '}')
     * @return void
     */
    public function set_delimiters($l = '{', $r = '}')
    {
        $this->l_delim = $l;
        $this->r_delim = $r;
    }

    /**
     * Parse a template
     *
     * Internal method that does the actual parsing
     *
     * @param string $template Template string
     * @param array $data Associative array of data
     * @param bool $return Whether to return or output
     * @return string|bool Parsed template or FALSE on failure
     */
    protected function _parse($template, $data, $return = false)
    {
        if ($template === '') {
            return false;
        }

        $replace = [];

        foreach ($data as $key => $val) {
            $replace = array_merge(
                $replace,
                is_array($val)
                    ? $this->_parse_pair($key, $val, $template)
                    : $this->_parse_single($key, (string) $val, $template)
            );
        }

        unset($data);
        $template = strtr($template, $replace);

        if ($return === false) {
            $this->CI->output->append_output($template);
        }

        return $template;
    }

    /**
     * Parse a single key/value
     *
     * Replaces a simple variable placeholder with its value
     *
     * @param string $key Variable name
     * @param string $val Variable value
     * @param string $string Template string (unused, kept for compatibility)
     * @return array Associative array with placeholder => value
     */
    protected function _parse_single($key, $val, $string)
    {
        return [$this->l_delim . $key . $this->r_delim => (string) $val];
    }

    /**
     * Parse a tag pair
     *
     * Parses tag pairs for looping: {tag} content... {/tag}
     *
     * @param string $variable Tag variable name
     * @param array $data Array of data rows for the loop
     * @param string $string Template string containing the tag pair
     * @return array Associative array with tag pair block => replaced content
     */
    protected function _parse_pair($variable, $data, $string)
    {
        $replace = [];

        // Find all tag pair blocks
        preg_match_all(
            '#' . preg_quote($this->l_delim . $variable . $this->r_delim) . '(.+?)' 
            . preg_quote($this->l_delim . '/' . $variable . $this->r_delim) . '#s',
            $string,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $str = '';

            // Loop through data rows
            foreach ($data as $row) {
                $temp = [];

                // Process each column in the row
                foreach ($row as $key => $val) {
                    if (is_array($val)) {
                        // Nested tag pair
                        $pair = $this->_parse_pair($key, $val, $match[1]);
                        if (!empty($pair)) {
                            $temp = array_merge($temp, $pair);
                        }
                        continue;
                    }

                    // Simple variable
                    $temp[$this->l_delim . $key . $this->r_delim] = $val;
                }

                // Replace variables in the inner content
                $str .= strtr($match[1], $temp);
            }

            // Replace the entire tag pair block
            $replace[$match[0]] = $str;
        }

        return $replace;
    }
}
