<?php

declare(strict_types=1);

namespace Kodhe\Parser\Contracts;

/**
 * Compiler Interface
 *
 * Defines the contract for template compilers.
 */
interface CompilerInterface
{
    /**
     * Compile tokens into output string
     *
     * @param list<TokenInterface> $tokens List of tokens to compile
     * @param array $data Data array for replacement
     * @return string Compiled output
     */
    public function compile(array $tokens, array $data): string;

    /**
     * Set delimiters for the compiler
     *
     * @param string $l Left delimiter
     * @param string $r Right delimiter
     * @return void
     */
    public function setDelimiters(string $l, string $r): void;
}
