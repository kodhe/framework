<?php

declare(strict_types=1);

namespace Kodhe\Parser;

use Kodhe\Parser\Cache\TemplateCache;
use Kodhe\Parser\Compiler\TemplateCompiler;
use Kodhe\Parser\Contracts\CacheInterface;
use Kodhe\Parser\Contracts\CompilerInterface;
use Kodhe\Parser\Contracts\LexerInterface;
use Kodhe\Parser\Contracts\ParserInterface;
use Kodhe\Parser\Context\ParseContext;
use Kodhe\Parser\Factory\ParserFactory;
use Kodhe\Parser\Lexer\TemplateLexer;
use Kodhe\Parser\Support\TemplateHelper;

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
class Parser implements ParserInterface
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
     * @var object|null
     */
    protected $CI;

    /**
     * Lexer instance
     */
    private ?LexerInterface $lexer = null;

    /**
     * Compiler instance
     */
    private ?CompilerInterface $compiler = null;

    /**
     * Cache instance
     */
    private ?CacheInterface $cache = null;

    /**
     * Parse context
     */
    private ?ParseContext $context = null;

    /**
     * Cache enabled flag
     */
    private bool $cacheEnabled = true;

    /**
     * Token cache for reuse
     *
     * @var array<string, array>
     */
    private array $tokenCache = [];

    /**
     * Compiled template cache
     *
     * @var array<string, string>
     */
    private array $compiledCache = [];

    /**
     * Class constructor
     *
     * @param LexerInterface|null $lexer Dependency injected lexer
     * @param CompilerInterface|null $compiler Dependency injected compiler
     * @param CacheInterface|null $cache Dependency injected cache
     * @param string $lDelim Left delimiter
     * @param string $rDelim Right delimiter
     * @return void
     */
    public function __construct(
        ?LexerInterface $lexer = null,
        ?CompilerInterface $compiler = null,
        ?CacheInterface $cache = null,
        string $lDelim = '{',
        string $rDelim = '}'
    ) {
        $this->CI = function_exists('kodhe') ? kodhe() : null;
        
        // Support dependency injection
        $this->lexer = $lexer;
        $this->compiler = $compiler;
        $this->cache = $cache;
        
        // Set delimiters
        if ($lDelim !== '{' || $rDelim !== '}') {
            $this->set_delimiters($lDelim, $rDelim);
        }

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

        // Update components if they exist
        if ($this->lexer !== null) {
            $this->lexer->setDelimiters($l, $r);
        }
        if ($this->compiler !== null) {
            $this->compiler->setDelimiters($l, $r);
        }
        if ($this->context !== null) {
            $this->context->setDelimiters($l, $r);
        }
    }

    /**
     * Enable caching
     */
    public function enableCache(): void
    {
        $this->cacheEnabled = true;
        if ($this->cache !== null && method_exists($this->cache, 'enable')) {
            $this->cache->enable();
        }
    }

    /**
     * Disable caching
     */
    public function disableCache(): void
    {
        $this->cacheEnabled = false;
        if ($this->cache !== null && method_exists($this->cache, 'disable')) {
            $this->cache->disable();
        }
    }

    /**
     * Clear compiled cache
     */
    public function clearCache(): void
    {
        $this->compiledCache = [];
        $this->tokenCache = [];
        if ($this->cache !== null && method_exists($this->cache, 'clear')) {
            $this->cache->clear();
        }
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

        // Use legacy method for full backward compatibility
        return $this->_parseLegacy($template, $data, $return);
    }

    /**
     * Legacy parse method for 100% CI3 compatibility
     * Uses the original regex-based approach
     */
    protected function _parseLegacy($template, $data, $return = false)
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

        if ($return === false && $this->CI !== null && isset($this->CI->output)) {
            $this->CI->output->append_output($template);
        }

        return $template;
    }

    /**
     * Parse using modern modular approach (lazy compilation)
     */
    protected function _parseModern($template, $data, $return = false)
    {
        if ($template === '') {
            return false;
        }

        // Initialize context
        $this->context = new ParseContext($this->l_delim, $this->r_delim, $data);
        $this->context->setCacheEnabled($this->cacheEnabled);

        // Lazy initialize lexer
        if ($this->lexer === null) {
            $this->lexer = new TemplateLexer($this->l_delim, $this->r_delim);
        }

        // Lazy initialize compiler
        if ($this->compiler === null) {
            $this->compiler = new TemplateCompiler($this->l_delim, $this->r_delim);
        }

        // Lazy initialize cache
        if ($this->cache === null) {
            $this->cache = new TemplateCache($this->cacheEnabled);
        }

        // Generate cache key
        $cacheKey = TemplateHelper::generateCacheKey($template, $data, $this->l_delim, $this->r_delim);

        // Check cache first
        if ($this->cacheEnabled && $this->cache->has($cacheKey)) {
            $result = $this->cache->get($cacheKey);
        } else {
            // Tokenize (with token reuse)
            $tokens = $this->getOrTokenize($template);
            
            // Compile
            $result = $this->compiler->compile($tokens, $data);
            
            // Cache result
            if ($this->cacheEnabled) {
                $this->cache->set($cacheKey, $result);
            }
        }

        if ($return === false && $this->CI !== null && isset($this->CI->output)) {
            $this->CI->output->append_output($result);
        }

        return $result;
    }

    /**
     * Get tokens from cache or tokenize
     */
    private function getOrTokenize(string $template): array
    {
        $cacheKey = md5($template . $this->l_delim . $this->r_delim);

        if (isset($this->tokenCache[$cacheKey])) {
            return $this->tokenCache[$cacheKey];
        }

        $tokens = $this->lexer->tokenize($template);

        // Limit cache size
        if (count($this->tokenCache) < 50) {
            $this->tokenCache[$cacheKey] = $tokens;
        }

        return $tokens;
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
