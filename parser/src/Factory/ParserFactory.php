<?php

declare(strict_types=1);

namespace Kodhe\Parser\Factory;

use Kodhe\Parser\Cache\TemplateCache;
use Kodhe\Parser\Compiler\TemplateCompiler;
use Kodhe\Parser\Contracts\CacheInterface;
use Kodhe\Parser\Contracts\CompilerInterface;
use Kodhe\Parser\Contracts\LexerInterface;
use Kodhe\Parser\Contracts\ParserInterface;
use Kodhe\Parser\Lexer\TemplateLexer;
use Kodhe\Parser\Parser;

/**
 * Parser Factory
 *
 * Creates Parser instances with dependencies using Builder pattern.
 */
class ParserFactory
{
    private ?LexerInterface $lexer = null;
    private ?CompilerInterface $compiler = null;
    private ?CacheInterface $cache = null;
    
    private string $lDelim = '{';
    private string $rDelim = '}';
    private bool $cacheEnabled = true;

    public function __construct()
    {
        // Default configuration
    }

    /**
     * Set custom lexer
     */
    public function setLexer(LexerInterface $lexer): self
    {
        $this->lexer = $lexer;
        return $this;
    }

    /**
     * Set custom compiler
     */
    public function setCompiler(CompilerInterface $compiler): self
    {
        $this->compiler = $compiler;
        return $this;
    }

    /**
     * Set custom cache
     */
    public function setCache(CacheInterface $cache): self
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * Set delimiters
     */
    public function setDelimiters(string $l, string $r): self
    {
        $this->lDelim = $l;
        $this->rDelim = $r;
        return $this;
    }

    /**
     * Enable or disable caching
     */
    public function setCacheEnabled(bool $enabled): self
    {
        $this->cacheEnabled = $enabled;
        return $this;
    }

    /**
     * Build parser instance with dependency injection
     */
    public function build(): ParserInterface
    {
        $lexer = $this->lexer ?? new TemplateLexer($this->lDelim, $this->rDelim);
        $compiler = $this->compiler ?? new TemplateCompiler($this->lDelim, $this->rDelim);
        $cache = $this->cache ?? new TemplateCache($this->cacheEnabled);

        return new Parser($lexer, $compiler, $cache, $this->lDelim, $this->rDelim);
    }

    /**
     * Create parser with default settings
     */
    public static function create(): ParserInterface
    {
        $factory = new self();
        return $factory->build();
    }

    /**
     * Create parser with custom delimiters
     */
    public static function createWithDelimiters(string $l, string $r): ParserInterface
    {
        $factory = new self();
        $factory->setDelimiters($l, $r);
        return $factory->build();
    }
}
