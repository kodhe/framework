<?php
/**
 * Compiler Interface
 *
 * @package CodeIgniter\Parser\Contracts
 */

namespace CodeIgniter\Parser\Contracts;

interface CompilerInterface
{
    /**
     * Compile tokens into output
     *
     * @param array $tokens
     * @param array $data
     * @return string
     */
    public function compile(array $tokens, array $data): string;

    /**
     * Set delimiters
     *
     * @param string $left
     * @param string $right
     * @return self
     */
    public function setDelimiters(string $left, string $right): self;
}
