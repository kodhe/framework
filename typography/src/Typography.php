<?php

declare(strict_types=0);

namespace Kodhe\Framework\Typography;

use Kodhe\Framework\Typography\Parsers\HtmlParser;
use Kodhe\Framework\Typography\Parsers\TextParser;
use Kodhe\Framework\Typography\Formatters\CharacterFormatter;
use Kodhe\Framework\Typography\Factory\FormatterFactory;
use Kodhe\Framework\Typography\Support\HtmlProtect;
use Kodhe\Framework\Typography\Support\RegexCache;
use Kodhe\Framework\Typography\ValueObjects\TypographicConfig;
use Kodhe\Framework\Typography\Exceptions/TypographyException;

/**
 * Typography Class
 *
 * @package         CodeIgniter
 * @subpackage      Libraries
 * @category        Helpers
 * @author          EllisLab Dev Team
 * @link            https://codeigniter.com/user_guide/libraries/typography.html
 */
class Typography
{

    /**
     * Block level elements that should not be wrapped inside <p> tags
     *
     * @var string
     */
    public $block_elements = 'address|blockquote|div|dl|fieldset|form|h\d|hr|noscript|object|ol|p|pre|script|table|ul';

    /**
     * Elements that should not have <p> and <br /> tags within them.
     *
     * @var string
     */
    public $skip_elements = 'p|pre|ol|ul|dl|object|table|h\d';

    /**
     * Tags we want the parser to completely ignore when splitting the string.
     *
     * @var string
     */
    public $inline_elements = 'a|abbr|acronym|b|bdo|big|br|button|cite|code|del|dfn|em|i|img|ins|input|label|map|kbd|q|samp|select|small|span|strong|sub|sup|textarea|tt|var';

    /**
     * array of block level elements that require inner content to be within another block level element
     *
     * @var array
     */
    public $inner_block_required = array('blockquote');

    /**
     * the last block element parsed
     *
     * @var string
     */
    public $last_block_element = '';

    /**
     * whether or not to protect quotes within { curly braces }
     *
     * @var bool
     */
    public $protect_braced_quotes = FALSE;

    /**
     * @var HtmlParser|null Internal HTML parser instance (lazy loaded)
     */
    private $htmlParser = null;

    /**
     * @var TextParser|null Internal text parser instance (lazy loaded)
     */
    private $textParser = null;

    /**
     * @var CharacterFormatter|null Internal character formatter instance (lazy loaded)
     */
    private $characterFormatter = null;

    /**
     * @var TypographicConfig Configuration object
     */
    private $config = null;

    /**
     * Constructor
     *
     * @param array $config Optional configuration
     */
    public function __construct(array $config = [])
    {
        if (!empty($config)) {
            $this->initialize($config);
        }
    }

    /**
     * Initialize the typography class with configuration.
     *
     * @param array $config
     * @return void
     */
    public function initialize(array $config = []): void
    {
        if (!empty($config)) {
            $this->config = TypographicConfig::fromArray($config);
            
            // Update public properties for backward compatibility
            foreach ($config as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
    }

    /**
     * Get configuration as array.
     *
     * @return array
     */
    public function getConfig(): array
    {
        if ($this->config === null) {
            $this->config = new TypographicConfig();
        }
        return $this->config->toArray();
    }

    /**
     * Auto Typography
     *
     * This function converts text, making it typographically correct:
     *  - Converts double spaces into paragraphs.
     *  - Converts single line breaks into <br /> tags
     *  - Converts single and double quotes into correctly facing curly quote entities.
     *  - Converts three dots into ellipsis.
     *  - Converts double dashes into em-dashes.
     *  - Converts two spaces into entities
     *
     * @param   string  $str
     * @param   bool    $reduce_linebreaks whether to reduce more then two consecutive newlines to two
     * @return  string
     */
    public function auto_typography($str, $reduce_linebreaks = FALSE)
    {
        if ($str === '') {
            return '';
        }

        // Standardize Newlines to make matching easier
        if (strpos($str, "\r") !== FALSE) {
            $str = str_replace(array("\r\n", "\r"), "\n", $str);
        }

        // Reduce line breaks
        if ($reduce_linebreaks === TRUE) {
            $str = preg_replace("/\n\n+/", "\n\n", $str);
        }

        // Extract HTML comments
        $html_comments = array();
        if (strpos($str, '<!--') !== FALSE && preg_match_all('#(<!\-\-.*?\-\->)#s', $str, $matches)) {
            for ($i = 0, $total = count($matches[0]); $i < $total; $i++) {
                $html_comments[] = $matches[0][$i];
                $str = str_replace($matches[0][$i], '{@HC'.$i.'}', $str);
            }
        }

        // Protect <pre> tags
        if (strpos($str, '<pre') !== FALSE) {
            $str = preg_replace_callback('#<pre.*?>.*?</pre>#si', [$this, '_protect_characters'], $str);
        }

        // Protect quotes within tags
        $str = preg_replace_callback('#<.+?>#si', [$this, '_protect_characters'], $str);

        // Protect braces if configured
        if ($this->protect_braced_quotes === TRUE) {
            $str = preg_replace_callback('#\{.+?\}#si', [$this, '_protect_characters'], $str);
        }

        // Convert inline tags to temporary markers
        $str = preg_replace('#<(/*)('.$this->inline_elements.')([ >])#i', '{@TAG}\\1\\2\\3', $str);

        // Split at every tag
        $chunks = preg_split('/(<(?:[^<>]+(?:"[^"]*"|\'[^\']*\')?)+>)/', $str, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        // Process chunks
        $result = '';
        $process = TRUE;
        $chunkCount = count($chunks) - 1;

        for ($i = 0; $i <= $chunkCount; $i++) {
            if (preg_match('#<(/*)('.$this->block_elements.').*?>#', $chunks[$i], $match)) {
                if (preg_match('#'.$this->skip_elements.'#', $match[2])) {
                    $process = ($match[1] === '/');
                }

                if ($match[1] === '') {
                    $this->last_block_element = $match[2];
                }

                $result .= $chunks[$i];
                continue;
            }

            if ($process === FALSE) {
                $result .= $chunks[$i];
                continue;
            }

            // Force newline at end
            if ($i === $chunkCount) {
                $chunks[$i] .= "\n";
            }

            // Format newlines
            $result .= $this->_format_newlines($chunks[$i]);
        }

        // Add opening paragraph if needed
        if (!preg_match('/^\s*<(?:'.$this->block_elements.')/i', $result)) {
            $result = preg_replace('/^(.*?)<('.$this->block_elements.')/i', '<p>$1</p><$2', $result);
        }

        // Apply character formatting
        $result = $this->format_characters($result);

        // Restore HTML comments
        for ($i = 0, $total = count($html_comments); $i < $total; $i++) {
            $pattern = '#(?(?=<p>\{@HC'.$i.'\})<p>\{@HC'.$i.'\}(\s*</p>)|\{@HC'.$i.'\})#s';
            $result = preg_replace($pattern, $html_comments[$i], $result);
        }

        // Final clean up table
        $table = [
            '/(<p[^>*?]>)<p>/'      => '$1',
            '#(</p>)+#'             => '</p>',
            '/(<p>\W*<p>)+/'        => '<p>',
            '#<p></p><('.$this->block_elements.')#'     => '<$1',
            '#(&nbsp;\s*)+<('.$this->block_elements.')#'=> '  <$2',
            '/\{@TAG\}/'            => '<',
            '/\{@DQ\}/'             => '"',
            '/\{@SQ\}/'             => "'",
            '/\{@DD\}/'             => '--',
            '/\{@NBS\}/'            => '  ',
            "/><p>\n/"              => ">\n<p>",
            '#</p></#'              => "</p>\n</"
        ];

        if ($reduce_linebreaks === TRUE) {
            $table['#<p>\n*</p>#'] = '';
        } else {
            $table['#<p></p>#'] = '<p>&nbsp;</p>';
        }

        return preg_replace(array_keys($table), $table, $result);
    }

    /**
     * Format Characters
     *
     * This function mainly converts double and single quotes
     * to curly entities, but it also converts em-dashes,
     * double spaces, and ampersands
     *
     * @param   string  $str
     * @return  string
     */
    public function format_characters($str)
    {
        if ($this->characterFormatter === null) {
            $this->characterFormatter = FormatterFactory::create('character');
        }

        return $this->characterFormatter->format($str);
    }

    /**
     * Convert newlines to HTML line breaks except within PRE tags
     *
     * @param   string  $str
     * @return  string
     */
    public function nl2br_except_pre($str)
    {
        if ($this->textParser === null) {
            $this->textParser = new TextParser();
        }

        return $this->textParser->nl2brExceptPre($str);
    }

    /**
     * Protect braced quotes
     * 
     * @param   string  $str
     * @return  string
     */
    public function protect_braced_quotes($str)
    {
        if ($this->textParser === null) {
            $this->textParser = new TextParser();
        }

        return $this->textParser->protectBracedQuotes($str);
    }

    /**
     * Get HTML parser instance (lazy loaded).
     *
     * @return HtmlParser
     */
    private function getHtmlParser(): HtmlParser
    {
        if ($this->htmlParser === null) {
            $this->htmlParser = new HtmlParser(new HtmlProtect());
        }

        return $this->htmlParser;
    }

    /**
     * Protect Characters callback
     *
     * @param   array   $match
     * @return  string
     */
    protected function _protect_characters($match)
    {
        return str_replace(array("'",'"','--','  '), array('{@SQ}', '{@DQ}', '{@DD}', '{@NBS}'), $match[0]);
    }

    /**
     * Format Newlines
     *
     * @param   string  $str
     * @return  string
     */
    protected function _format_newlines($str)
    {
        if ($str === '' || (strpos($str, "\n") === FALSE && !in_array($this->last_block_element, $this->inner_block_required))) {
            return $str;
        }

        $str = str_replace("\n\n", "</p>\n\n<p>", $str);
        $str = preg_replace("/([^\n])(\n)([^\n])/", '\\1<br />\\2\\3', $str);

        if ($str !== "\n") {
            $str = '<p>' . rtrim($str) . '</p>';
        }

        return preg_replace('/<p><\/p>(.*)/', '\\1', $str, 1);
    }

    /**
     * Clear cached instances.
     *
     * @return void
     */
    public static function clearCache(): void
    {
        RegexCache::clear();
        FormatterFactory::clearCache();
        CharacterFormatter::clearCache();
    }
}
