<?php

namespace Kodhe\Framework\View\Contracts;

/**
 * Interface VariantManagerInterface
 *
 * @package Kodhe\Framework\View\Contracts
 */
interface VariantManagerInterface
{
    /**
     * Detect variant from request
     *
     * @return string
     */
    public function detect(): string;

    /**
     * Set variant manually
     *
     * @param string $variant
     * @return self
     */
    public function setVariant(string $variant): self;

    /**
     * Get current variant
     *
     * @return string
     */
    public function getVariant(): string;

    /**
     * Check if current variant matches
     *
     * @param string $variant
     * @return bool
     */
    public function is(string $variant): bool;

    /**
     * Get all available variants
     *
     * @return array
     */
    public function getVariants(): array;
}
