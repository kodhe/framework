<?php
/**
 * Template Lexer - Tokenizes template strings
 *
 * @package CodeIgniter\Parser\Lexer
 */

namespace Kodhe\Framework\Parser\Lexer;

use Kodhe\Framework\Parser\Contracts\LexerInterface;
use Kodhe\Framework\Parser\ValueObjects\Token;

class TemplateLexer implements LexerInterface
{
    /**
     * @var string
     */
    private $leftDelimiter = '{';

    /**
     * @var string
     */
    private $rightDelimiter = '}';

    /**
     * @var int
     */
    private $leftLength = 1;

    /**
     * @var int
     */
    private $rightLength = 1;

    /**
     * @inheritDoc
     */
    public function tokenize(string $template): array
    {
        $tokens = [];
        $position = 0;
        $length = strlen($template);

        while ($position < $length) {
            // Check for left delimiter
            if (substr($template, $position, $this->leftLength) === $this->leftDelimiter) {
                // Find closing delimiter
                $closePos = strpos($template, $this->rightDelimiter, $position + $this->leftLength);
                
                if ($closePos === false) {
                    // No closing delimiter, treat as text
                    $tokens[] = new Token(Token::TYPE_TEXT, substr($template, $position), $position);
                    break;
                }

                // Add any preceding text
                if ($position > 0 || $closePos > $this->leftLength) {
                    $textBefore = substr($template, 0, $position);
                    if ($textBefore !== '' && (empty($tokens) || $tokens[count($tokens) - 1]->getType() !== Token::TYPE_TEXT)) {
                        // Text already handled in previous iterations
                    }
                }

                // Extract content between delimiters
                $contentStart = $position + $this->leftLength;
                $content = substr($template, $contentStart, $closePos - $contentStart);
                $content = trim($content);

                // Determine token type
                $tokenType = $this->determineTokenType($content);
                $tokens[] = new Token($tokenType, $content, $position);

                $position = $closePos + $this->rightLength;
            } else {
                // Regular text - collect until next delimiter or end
                $nextDelimiter = strpos($template, $this->leftDelimiter, $position);
                
                if ($nextDelimiter === false) {
                    // No more delimiters, rest is text
                    $text = substr($template, $position);
                    if ($text !== '') {
                        $tokens[] = new Token(Token::TYPE_TEXT, $text, $position);
                    }
                    break;
                } else {
                    // Text until next delimiter
                    $text = substr($template, $position, $nextDelimiter - $position);
                    if ($text !== '') {
                        $tokens[] = new Token(Token::TYPE_TEXT, $text, $position);
                    }
                    $position = $nextDelimiter;
                }
            }
        }

        return $tokens;
    }

    /**
     * Determine token type from content
     *
     * @param string $content
     * @return string
     */
    private function determineTokenType(string $content): string
    {
        // Check for loop start: loop variable
        if (preg_match('/^loop\s+(\w+)$/i', $content)) {
            return Token::TYPE_LOOP_START;
        }

        // Check for loop end: /loop
        if (preg_match('/^\/loop$/i', $content)) {
            return Token::TYPE_LOOP_END;
        }

        // Check for conditional start: if condition
        if (preg_match('/^if\s+(.+)$/i', $content, $matches)) {
            return Token::TYPE_CONDITIONAL_START;
        }

        // Check for conditional end: /if
        if (preg_match('/^\/if$/i', $content)) {
            return Token::TYPE_CONDITIONAL_END;
        }

        // Check for include: include "file"
        if (preg_match('/^include\s+["\']([^"\']+)["\']$/i', $content)) {
            return Token::TYPE_INCLUDE;
        }

        // Default to variable
        return Token::TYPE_VARIABLE;
    }

    /**
     * @inheritDoc
     */
    public function setDelimiters(string $left, string $right): self
    {
        $this->leftDelimiter = $left;
        $this->rightDelimiter = $right;
        $this->leftLength = strlen($left);
        $this->rightLength = strlen($right);
        
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
}
