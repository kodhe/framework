<?php
/**
 * Lexer Interface
 *
 * @package CodeIgniter\Parser\Contracts
 */

namespace Kodhe\Parser\Contracts;

use Kodhe\Parser\ValueObjects\Token;

interface LexerInterface
{
    /**
     * Tokenize template string
     *
     * @param string $template
     * @return Token[]
     */
    public function tokenize(string $template): array;

    /**
     * Set delimiters
     *
     * @param string $left
     * @param string $right
     * @return self
     */
    public function setDelimiters(string $left, string $right): self;
}
