<?php
/**
 * Parser - CodeIgniter 3 Compatible Template Parser
 *
 * @package CodeIgniter\Parser
 */

namespace CodeIgniter\Parser;

use CodeIgniter\Parser\Contracts\ParserInterface;
use CodeIgniter\Parser\Contracts\LexerInterface;
use CodeIgniter\Parser\Contracts\CompilerInterface;
use CodeIgniter\Parser\Contracts\CacheInterface;
use CodeIgniter\Parser\Lexer\TemplateLexer;
use CodeIgniter\Parser\Compiler\TemplateCompiler;
use CodeIgniter\Parser\Cache\TemplateCache;

class Parser implements ParserInterface
{
    /**
     * @var LexerInterface
     */
    private $lexer;

    /**
     * @var CompilerInterface
     */
    private $compiler;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var string
     */
    private $leftDelimiter = '{';

    /**
     * @var string
     */
    private $rightDelimiter = '}';

    /**
     * @var array
     */
    private $viewPaths = [];

    /**
     * @var callable|null
     */
    private $includeCallback = null;

    /**
     * @var bool
     */
    private $cacheEnabled = true;

    /**
     * Constructor
     *
     * @param LexerInterface|null   $lexer
     * @param CompilerInterface|null $compiler
     * @param CacheInterface|null   $cache
     */
    public function __construct(
        ?LexerInterface $lexer = null,
        ?CompilerInterface $compiler = null,
        ?CacheInterface $cache = null
    ) {
        // Dependency Injection with defaults
        $this->lexer = $lexer ?? new TemplateLexer();
        $this->compiler = $compiler ?? new TemplateCompiler();
        $this->cache = $cache ?? new TemplateCache();
    }

    /**
     * Set view paths for includes
     *
     * @param array $paths
     * @return self
     */
    public function setViewPaths(array $paths): self
    {
        $this->viewPaths = $paths;
        if (method_exists($this->compiler, 'setViewPaths')) {
            $this->compiler->setViewPaths($paths);
        }
        return $this;
    }

    /**
     * Set include callback
     *
     * @param callable $callback
     * @return self
     */
    public function setIncludeCallback(callable $callback): self
    {
        $this->includeCallback = $callback;
        if (method_exists($this->compiler, 'setIncludeCallback')) {
            $this->compiler->setIncludeCallback($callback);
        }
        return $this;
    }

    /**
     * Enable or disable cache
     *
     * @param bool $enabled
     * @return self
     */
    public function setCacheEnabled(bool $enabled): self
    {
        $this->cacheEnabled = $enabled;
        if (method_exists($this->cache, 'setEnabled')) {
            $this->cache->setEnabled($enabled);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function parse(string $template, array $data = [], bool $return = false): string
    {
        // Generate cache key for lazy compilation
        $cacheKey = TemplateCache::generateKey($template, $data);

        // Try to get from cache (lazy compile)
        if ($this->cacheEnabled) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $return ? $cached : $this->output($cached);
            }
        }

        // Set delimiters on lexer and compiler
        $this->lexer->setDelimiters($this->leftDelimiter, $this->rightDelimiter);
        $this->compiler->setDelimiters($this->leftDelimiter, $this->rightDelimiter);

        // Tokenize template
        $tokens = $this->lexer->tokenize($template);

        // Compile tokens with data
        $result = $this->compiler->compile($tokens, $data);

        // Cache the result
        if ($this->cacheEnabled) {
            $this->cache->set($cacheKey, $result);
        }

        return $return ? $result : $this->output($result);
    }

    /**
     * @inheritDoc
     * Alias for parse() - CI3 compatibility
     */
    public function parse_string(string $view, array $data = [], bool $return = false): string
    {
        // In CI3 context, this would load a view file
        // For standalone usage, treat as template string
        return $this->parse($view, $data, $return);
    }

    /**
     * @inheritDoc
     */
    public function set_delimiters(string $l = '{', string $r = '}'): self
    {
        $this->leftDelimiter = $l;
        $this->rightDelimiter = $r;
        
        // Propagate to lexer and compiler
        $this->lexer->setDelimiters($l, $r);
        $this->compiler->setDelimiters($l, $r);
        
        return $this;
    }

    /**
     * Output result (CI3 compatibility - echoes by default)
     *
     * @param string $content
     * @return string
     */
    private function output(string $content): string
    {
        // In CI3, this would be echoed directly
        // For testing and flexibility, we return it
        // The CI3 wrapper can echo it
        return $content;
    }

    /**
     * Get lexer instance
     *
     * @return LexerInterface
     */
    public function getLexer(): LexerInterface
    {
        return $this->lexer;
    }

    /**
     * Get compiler instance
     *
     * @return CompilerInterface
     */
    public function getCompiler(): CompilerInterface
    {
        return $this->compiler;
    }

    /**
     * Get cache instance
     *
     * @return CacheInterface
     */
    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    /**
     * Clear cache
     *
     * @return self
     */
    public function clearCache(): self
    {
        if (method_exists($this->cache, 'clear')) {
            $this->cache->clear();
        }
        return $this;
    }
}
