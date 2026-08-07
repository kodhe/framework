<?php

declare(strict_types=1);

namespace Kodhe\Parser\Lexer;

use Kodhe\Parser\Contracts\LexerInterface;
use Kodhe\Parser\Contracts\TokenInterface;
use Kodhe\Parser\ValueObjects\Token;

/**
 * Template Lexer
 *
 * Tokenizes template strings into tokens for compilation.
 */
class TemplateLexer implements LexerInterface
{
    protected string $lDelim = '{';
    protected string $rDelim = '}';

    /**
     * @var list<Token>
     */
    private array $tokenPool = [];

    public function __construct(string $lDelim = '{', string $rDelim = '}')
    {
        $this->lDelim = $lDelim;
        $this->rDelim = $rDelim;
    }

    public function setDelimiters(string $l, string $r): void
    {
        $this->lDelim = $l;
        $this->rDelim = $r;
    }

    /**
     * @return list<TokenInterface>
     */
    public function tokenize(string $template): array
    {
        $tokens = [];
        $length = strlen($template);
        $position = 0;
        $textStart = 0;

        while ($position < $length) {
            // Look for opening delimiter
            $openPos = strpos($template, $this->lDelim, $position);

            if ($openPos === false) {
                // No more tags, rest is text
                if ($textStart < $length) {
                    $tokens[] = $this->createToken(
                        TokenInterface::TYPE_TEXT,
                        substr($template, $textStart),
                        $textStart
                    );
                }
                break;
            }

            // Add text before the tag
            if ($openPos > $textStart) {
                $tokens[] = $this->createToken(
                    TokenInterface::TYPE_TEXT,
                    substr($template, $textStart, $openPos - $textStart),
                    $textStart
                );
            }

            // Find closing delimiter
            $closePos = strpos($template, $this->rDelim, $openPos);

            if ($closePos === false) {
                // Unclosed tag, treat as text
                $tokens[] = $this->createToken(
                    TokenInterface::TYPE_TEXT,
                    substr($template, $openPos),
                    $openPos
                );
                break;
            }

            // Extract tag content
            $tagContent = substr($template, $openPos + strlen($this->lDelim), $closePos - $openPos - strlen($this->lDelim));
            $tagName = trim($tagContent);

            // Check for closing tag
            if (str_starts_with($tagName, '/')) {
                // Closing tag pair
                $name = substr($tagName, 1);
                $tokens[] = $this->createToken(
                    TokenInterface::TYPE_TAG_PAIR_CLOSE,
                    $this->lDelim . $tagContent . $this->rDelim,
                    $openPos,
                    $name
                );
            } else {
                // Opening tag or variable
                $tokens[] = $this->createToken(
                    TokenInterface::TYPE_VARIABLE,
                    $this->lDelim . $tagContent . $this->rDelim,
                    $openPos,
                    $tagName
                );
            }

            $position = $closePos + strlen($this->rDelim);
            $textStart = $position;
        }

        return $tokens;
    }

    /**
     * Create token with optional pooling for reuse
     */
    private function createToken(string $type, string $value, int $position, ?string $name = null): Token
    {
        // Simple token reuse for common patterns
        $poolKey = $type . ':' . $value;
        
        if (isset($this->tokenPool[$poolKey])) {
            $cached = $this->tokenPool[$poolKey];
            return new Token($type, $value, $position, $name);
        }

        $token = new Token($type, $value, $position, $name);
        
        // Limit pool size
        if (count($this->tokenPool) < 100) {
            $this->tokenPool[$poolKey] = $token;
        }

        return $token;
    }
}
