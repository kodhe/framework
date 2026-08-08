<?php
/**
 * Template Compiler - Interpreter pattern for template compilation
 *
 * @package CodeIgniter\Parser\Compiler
 */

namespace Kodhe\Parser\Compiler;

use Kodhe\Parser\Contracts\CompilerInterface;
use Kodhe\Parser\ValueObjects\Token;

class TemplateCompiler implements CompilerInterface
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
     * @var array
     */
    private $viewPaths = [];

    /**
     * @var callable|null
     */
    private $includeCallback = null;

    /**
     * Set view paths for includes
     *
     * @param array $paths
     * @return self
     */
    public function setViewPaths(array $paths): self
    {
        $this->viewPaths = $paths;
        return $this;
    }

    /**
     * Set include callback
     *
     * @param callable $callback
     * @return self
     */
    public function setIncludeCallback(callable $callback): self
    {
        $this->includeCallback = $callback;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function compile(array $tokens, array $data): string
    {
        $output = '';
        $tokenCount = count($tokens);
        
        for ($i = 0; $i < $tokenCount; $i++) {
            $token = $tokens[$i];
            
            switch ($token->getType()) {
                case Token::TYPE_TEXT:
                    $output .= $token->getValue();
                    break;

                case Token::TYPE_VARIABLE:
                    $output .= $this->resolveVariable($token->getVariableName(), $data);
                    break;

                case Token::TYPE_LOOP_START:
                    $loopResult = $this->processLoop($tokens, $i, $data);
                    $output .= $loopResult['content'];
                    $i = $loopResult['endIndex'];
                    break;

                case Token::TYPE_CONDITIONAL_START:
                    $conditionalResult = $this->processConditional($tokens, $i, $data);
                    $output .= $conditionalResult['content'];
                    $i = $conditionalResult['endIndex'];
                    break;

                case Token::TYPE_INCLUDE:
                    $includeContent = $this->processInclude($token->getValue());
                    if ($includeContent !== null) {
                        $output .= $includeContent;
                    }
                    break;

                case Token::TYPE_LOOP_END:
                case Token::TYPE_CONDITIONAL_END:
                    // These are handled by their start counterparts
                    break;
            }
        }

        return $output;
    }

    /**
     * Resolve variable from data
     *
     * @param string $name
     * @param array  $data
     * @return string
     */
    private function resolveVariable(string $name, array $data): string
    {
        // Support dot notation for nested arrays
        $parts = explode('.', $name);
        $value = $data;

        foreach ($parts as $part) {
            if (is_array($value) && array_key_exists($part, $value)) {
                $value = $value[$part];
            } else {
                return '';
            }
        }

        // Convert to string if not already
        if (is_array($value) || is_object($value)) {
            return '';
        }

        return (string) $value;
    }

    /**
     * Process loop construct
     *
     * @param array $tokens
     * @param int   $startIndex
     * @param array $data
     * @return array
     */
    private function processLoop(array $tokens, int $startIndex, array $data): array
    {
        $startToken = $tokens[$startIndex];
        $content = $startToken->getValue();
        
        // Extract loop variable name
        preg_match('/^loop\s+(\w+)$/i', $content, $matches);
        $loopVar = $matches[1] ?? 'items';

        // Find matching end tag and collect inner tokens
        $innerTokens = [];
        $depth = 1;
        $endIndex = $startIndex + 1;
        $tokenCount = count($tokens);

        while ($endIndex < $tokenCount && $depth > 0) {
            $token = $tokens[$endIndex];
            
            if ($token->getType() === Token::TYPE_LOOP_START) {
                $depth++;
            } elseif ($token->getType() === Token::TYPE_LOOP_END) {
                $depth--;
            }

            if ($depth > 0) {
                $innerTokens[] = $token;
            }
            
            $endIndex++;
        }

        // Get the loop data
        $loopData = $this->resolveVariable($loopVar, $data);
        
        if (!is_array($loopData)) {
            return ['content' => '', 'endIndex' => $endIndex - 1];
        }

        // Compile each iteration
        $output = '';
        foreach ($loopData as $item) {
            $iterationData = array_merge($data, [$loopVar => $item]);
            $output .= $this->compile($innerTokens, $iterationData);
        }

        return ['content' => $output, 'endIndex' => $endIndex - 1];
    }

    /**
     * Process conditional construct
     *
     * @param array $tokens
     * @param int   $startIndex
     * @param array $data
     * @return array
     */
    private function processConditional(array $tokens, int $startIndex, array $data): array
    {
        $startToken = $tokens[$startIndex];
        $content = $startToken->getValue();
        
        // Extract condition
        preg_match('/^if\s+(.+)$/i', $content, $matches);
        $condition = $matches[1] ?? '';

        // Find matching end tag and collect inner tokens
        $innerTokens = [];
        $depth = 1;
        $endIndex = $startIndex + 1;
        $tokenCount = count($tokens);

        while ($endIndex < $tokenCount && $depth > 0) {
            $token = $tokens[$endIndex];
            
            if ($token->getType() === Token::TYPE_CONDITIONAL_START) {
                $depth++;
            } elseif ($token->getType() === Token::TYPE_CONDITIONAL_END) {
                $depth--;
            }

            if ($depth > 0) {
                $innerTokens[] = $token;
            }
            
            $endIndex++;
        }

        // Evaluate condition
        $conditionMet = $this->evaluateCondition($condition, $data);

        if (!$conditionMet) {
            return ['content' => '', 'endIndex' => $endIndex - 1];
        }

        // Compile inner content
        $output = $this->compile($innerTokens, $data);

        return ['content' => $output, 'endIndex' => $endIndex - 1];
    }

    /**
     * Evaluate condition
     *
     * @param string $condition
     * @param array  $data
     * @return bool
     */
    private function evaluateCondition(string $condition, array $data): bool
    {
        $condition = trim($condition);
        
        // Check for negation
        $negated = false;
        if (strpos($condition, '!') === 0) {
            $negated = true;
            $condition = trim(substr($condition, 1));
        }

        // Check if variable exists and is truthy
        $value = $this->resolveVariable($condition, $data);
        
        $result = !empty($value) && $value !== '0' && $value !== 'false';
        
        return $negated ? !$result : $result;
    }

    /**
     * Process include directive
     *
     * @param string $content
     * @return string|null
     */
    private function processInclude(string $content): ?string
    {
        preg_match('/^include\s+["\']([^"\']+)["\']$/i', $content, $matches);
        $filename = $matches[1] ?? '';

        if ($filename === '') {
            return null;
        }

        // Use callback if provided
        if ($this->includeCallback !== null) {
            $callback = $this->includeCallback;
            return $callback($filename);
        }

        // Try to find file in view paths
        foreach ($this->viewPaths as $path) {
            $filePath = rtrim($path, '/') . '/' . $filename;
            if (file_exists($filePath)) {
                return file_get_contents($filePath);
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function setDelimiters(string $left, string $right): self
    {
        $this->leftDelimiter = $left;
        $this->rightDelimiter = $right;
        return $this;
    }
}
