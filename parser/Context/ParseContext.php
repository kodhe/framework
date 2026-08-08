<?php
/**
 * Parse Context - Manages parsing context and state
 *
 * @package CodeIgniter\Parser\Context
 */

namespace Kodhe\Parser\Context;

class ParseContext
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * @var string
     */
    private $template = '';

    /**
     * @var string
     */
    private $leftDelimiter = '{';

    /**
     * @var string
     */
    private $rightDelimiter = '}';

    /**
     * @var bool
     */
    private $cacheEnabled = true;

    /**
     * @var string|null
     */
    private $cacheKey = null;

    /**
     * Set parsing data
     *
     * @param array $data
     * @return self
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get parsing data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set template content
     *
     * @param string $template
     * @return self
     */
    public function setTemplate(string $template): self
    {
        $this->template = $template;
        return $this;
    }

    /**
     * Get template content
     *
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * Set delimiters
     *
     * @param string $left
     * @param string $right
     * @return self
     */
    public function setDelimiters(string $left, string $right): self
    {
        $this->leftDelimiter = $left;
        $this->rightDelimiter = $right;
        return $this;
    }

    /**
     * Get left delimiter
     *
     * @return string
     */
    public function getLeftDelimiter(): string
    {
        return $this->leftDelimiter;
    }

    /**
     * Get right delimiter
     *
     * @return string
     */
    public function getRightDelimiter(): string
    {
        return $this->rightDelimiter;
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
        return $this;
    }

    /**
     * Is cache enabled?
     *
     * @return bool
     */
    public function isCacheEnabled(): bool
    {
        return $this->cacheEnabled;
    }

    /**
     * Set cache key
     *
     * @param string|null $key
     * @return self
     */
    public function setCacheKey(?string $key): self
    {
        $this->cacheKey = $key;
        return $this;
    }

    /**
     * Get cache key
     *
     * @return string|null
     */
    public function getCacheKey(): ?string
    {
        return $this->cacheKey;
    }

    /**
     * Create context from array
     *
     * @param array $config
     * @return self
     */
    public static function fromArray(array $config): self
    {
        $context = new self();
        
        if (isset($config['data'])) {
            $context->setData($config['data']);
        }
        
        if (isset($config['template'])) {
            $context->setTemplate($config['template']);
        }
        
        if (isset($config['left_delimiter'])) {
            $context->setDelimiters(
                $config['left_delimiter'],
                $config['right_delimiter'] ?? '}'
            );
        }
        
        if (isset($config['cache_enabled'])) {
            $context->setCacheEnabled($config['cache_enabled']);
        }
        
        if (isset($config['cache_key'])) {
            $context->setCacheKey($config['cache_key']);
        }
        
        return $context;
    }
}
