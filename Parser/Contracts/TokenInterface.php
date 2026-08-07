<?php
/**
 * Token Interface
 *
 * @package CodeIgniter\Parser\Contracts
 */

namespace CodeIgniter\Parser\Contracts;

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
