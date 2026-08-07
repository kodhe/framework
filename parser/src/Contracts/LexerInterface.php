<?php

declare(strict_types=1);

namespace Kodhe\Parser\Contracts;

/**
 * Lexer Interface
 *
 * Defines the contract for template lexers.
 */
interface LexerInterface
{
    /**
     * Tokenize a template string
     *
     * @param string $template Template string to tokenize
     * @return list<TokenInterface> List of tokens
     */
    public function tokenize(string $template): array;

    /**
     * Set delimiters for the lexer
     *
     * @param string $l Left delimiter
     * @param string $r Right delimiter
     * @return void
     */
    public function setDelimiters(string $l, string $r): void;
}
