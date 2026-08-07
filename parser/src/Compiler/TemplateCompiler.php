<?php

declare(strict_types=1);

namespace Kodhe\Parser\Compiler;

use Kodhe\Parser\Contracts\CompilerInterface;
use Kodhe\Parser\Contracts\TokenInterface;

/**
 * Template Compiler
 *
 * Compiles tokens into output using the Interpreter pattern.
 */
class TemplateCompiler implements CompilerInterface
{
    protected string $lDelim = '{';
    protected string $rDelim = '}';

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
     * Compile tokens into output string
     *
     * @param list<TokenInterface> $tokens
     */
    public function compile(array $tokens, array $data): string
    {
        return $this->interpretTokens($tokens, $data);
    }

    /**
     * Interpret tokens with data (Interpreter pattern)
     *
     * @param list<TokenInterface> $tokens
     */
    private function interpretTokens(array $tokens, array $data, bool $inLoop = false): string
    {
        $output = '';
        $tokenCount = count($tokens);

        for ($i = 0; $i < $tokenCount; $i++) {
            $token = $tokens[$i];

            switch ($token->getType()) {
                case TokenInterface::TYPE_TEXT:
                    $output .= $token->getValue();
                    break;

                case TokenInterface::TYPE_VARIABLE:
                    $name = $token->getName();
                    if ($name !== null && isset($data[$name])) {
                        $value = $data[$name];
                        if (is_array($value)) {
                            // Handle tag pairs - find matching close tag
                            $pairContent = $this->extractPairContent($tokens, $i + 1, $name);
                            if ($pairContent !== null) {
                                $output .= $this->processTagPair($name, $value, $pairContent['tokens'], $pairContent['endIndex']);
                                $i = $pairContent['endIndex'];
                            }
                        } else {
                            $output .= (string) $value;
                        }
                    }
                    break;

                case TokenInterface::TYPE_TAG_PAIR_CLOSE:
                    // Should be handled by tag pair processing
                    break;
            }
        }

        return $output;
    }

    /**
     * Extract content between tag pair
     *
     * @param list<TokenInterface> $tokens
     * @return array{tokens: list<TokenInterface>, endIndex: int}|null
     */
    private function extractPairContent(array $tokens, int $startIndex, string $tagName): ?array
    {
        $contentTokens = [];
        $nestingLevel = 0;

        for ($i = $startIndex; $i < count($tokens); $i++) {
            $token = $tokens[$i];

            if ($token->getType() === TokenInterface::TYPE_VARIABLE && $token->getName() === $tagName) {
                $nestingLevel++;
                $contentTokens[] = $token;
            } elseif ($token->getType() === TokenInterface::TYPE_TAG_PAIR_CLOSE && $token->getName() === $tagName) {
                if ($nestingLevel === 0) {
                    return ['tokens' => $contentTokens, 'endIndex' => $i];
                }
                $nestingLevel--;
                $contentTokens[] = $token;
            } else {
                $contentTokens[] = $token;
            }
        }

        return null;
    }

    /**
     * Process a tag pair (loop)
     *
     * @param list<TokenInterface> $contentTokens
     */
    private function processTagPair(string $tagName, array $data, array $contentTokens, int $endIndex): string
    {
        $result = '';

        foreach ($data as $row) {
            if (is_array($row)) {
                $result .= $this->interpretTokens($contentTokens, $row, true);
            }
        }

        return $result;
    }
}
