<?php

namespace Kodhe\Framework\View\Variant;

use Kodhe\Framework\View\Contracts\VariantManagerInterface;

/**
 * Class VariantManager
 *
 * @package Kodhe\Framework\View\Variant
 */
class VariantManager implements VariantManagerInterface
{
    /**
     * @var VariantResolver
     */
    protected $resolver;

    /**
     * @var string
     */
    protected $currentVariant;

    /**
     * @var bool
     */
    protected $autoDetect = true;

    /**
     * Create a new VariantManager instance
     *
     * @param VariantResolver|null $resolver
     */
    public function __construct(?VariantResolver $resolver = null)
    {
        $this->resolver = $resolver ?? new VariantResolver();
    }

    /**
     * Detect variant from request
     *
     * @return string
     */
    public function detect(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return $this->resolver->resolve($userAgent);
    }

    /**
     * Set variant manually
     *
     * @param string $variant
     * @return self
     */
    public function setVariant(string $variant): self
    {
        $this->currentVariant = $variant;
        $this->autoDetect = false;
        return $this;
    }

    /**
     * Get current variant
     *
     * @return string
     */
    public function getVariant(): string
    {
        if ($this->currentVariant) {
            return $this->currentVariant;
        }

        if ($this->autoDetect) {
            $this->currentVariant = $this->detect();
        }

        return $this->currentVariant ?: 'desktop';
    }

    /**
     * Check if current variant matches
     *
     * @param string $variant
     * @return bool
     */
    public function is(string $variant): bool
    {
        return $this->getVariant() === $variant;
    }

    /**
     * Get all available variants
     *
     * @return array
     */
    public function getVariants(): array
    {
        return array_keys($this->resolver->getVariants());
    }

    /**
     * Enable auto detection
     *
     * @return self
     */
    public function enableAutoDetect(): self
    {
        $this->autoDetect = true;
        return $this;
    }

    /**
     * Disable auto detection
     *
     * @return self
     */
    public function disableAutoDetect(): self
    {
        $this->autoDetect = false;
        return $this;
    }
}
