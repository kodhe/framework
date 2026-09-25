<?php
/**
 * Token Interface
 *
 * @package Kodhe\Framework\Parser\Contracts
 */

namespace Kodhe\Framework\Parser\Contracts;

interface TokenInterface
{
    /**
     * Get token type
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Get token value
     *
     * @return string
     */
    public function getValue(): string;

    /**
     * Get token position
     *
     * @return int
     */
    public function getPosition(): int;
}
