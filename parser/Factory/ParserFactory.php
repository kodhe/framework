<?php
/**
 * Parser Factory - Factory + Builder pattern for creating parser instances
 *
 * @package CodeIgniter\Parser\Factory
 */

namespace Kodhe\Framework\Parser\Factory;

use Kodhe\Framework\Parser\Contracts\LexerInterface;
use Kodhe\Framework\Parser\Contracts\CompilerInterface;
use Kodhe\Framework\Parser\Contracts\CacheInterface;
use Kodhe\Framework\Parser\Lexer\TemplateLexer;
use Kodhe\Framework\Parser\Compiler\TemplateCompiler;
use Kodhe\Framework\Parser\Cache\TemplateCache;
use Kodhe\Framework\Parser\Parser;

class ParserFactory
{
    /**
     * @var LexerInterface|null
     */
    private $lexer = null;

    /**
     * @var CompilerInterface|null
     */
    private $compiler = null;

    /**
     * @var CacheInterface|null
     */
    private $cache = null;

    /**
     * @var array
     */
    private $config = [];

    /**
     * Set custom lexer
     *
     * @param LexerInterface $lexer
     * @return self
     */
    public function setLexer(LexerInterface $lexer): self
    {
        $this->lexer = $lexer;
        return $this;
    }

    /**
     * Set custom compiler
     *
     * @param CompilerInterface $compiler
     * @return self
     */
    public function setCompiler(CompilerInterface $compiler): self
    {
        $this->compiler = $compiler;
        return $this;
    }

    /**
     * Set custom cache
     *
     * @param CacheInterface $cache
     * @return self
     */
    public function setCache(CacheInterface $cache): self
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * Set configuration
     *
     * @param array $config
     * @return self
     */
    public function setConfig(array $config): self
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Create parser instance
     *
     * @return Parser
     */
    public function create(): Parser
    {
        $lexer = $this->lexer ?? new TemplateLexer();
        $compiler = $this->compiler ?? new TemplateCompiler();
        $cache = $this->cache ?? new TemplateCache();

        $parser = new Parser($lexer, $compiler, $cache);

        // Apply configuration
        if (!empty($this->config)) {
            if (isset($this->config['left_delimiter']) && isset($this->config['right_delimiter'])) {
                $parser->set_delimiters(
                    $this->config['left_delimiter'],
                    $this->config['right_delimiter']
                );
            }

            if (isset($this->config['cache_enabled'])) {
                $cache->setEnabled($this->config['cache_enabled']);
            }

            if (isset($this->config['view_paths'])) {
                $compiler->setViewPaths($this->config['view_paths']);
            }

            if (isset($this->config['include_callback']) && is_callable($this->config['include_callback'])) {
                $compiler->setIncludeCallback($this->config['include_callback']);
            }
        }

        return $parser;
    }

    /**
     * Create parser with default configuration
     *
     * @return Parser
     */
    public static function make(): Parser
    {
        $factory = new self();
        return $factory->create();
    }

    /**
     * Create parser with custom configuration
     *
     * @param array $config
     * @return Parser
     */
    public static function makeWithConfig(array $config): Parser
    {
        $factory = new self();
        $factory->setConfig($config);
        return $factory->create();
    }

    /**
     * Create parser with custom components
     *
     * @param LexerInterface   $lexer
     * @param CompilerInterface $compiler
     * @param CacheInterface   $cache
     * @return Parser
     */
    public static function makeWithComponents(
        LexerInterface $lexer,
        CompilerInterface $compiler,
        CacheInterface $cache
    ): Parser {
        $factory = new self();
        $factory->setLexer($lexer)
                ->setCompiler($compiler)
                ->setCache($cache);
        return $factory->create();
    }
}
