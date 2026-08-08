<?php

declare(strict_types=0);

namespace Kodhe\Framework\Xmlrpcs\Factory;

use Kodhe\Framework\Xmlrpcs\Contracts\MethodHandlerInterface;
use Kodhe\Framework\Xmlrpcs\Handlers\FunctionHandler;
use Kodhe\Framework\Xmlrpcs\Handlers\ObjectMethodHandler;
use Kodhe\Framework\Xmlrpcs\Handlers\SystemMethodHandler;

/**
 * Factory for creating method handlers
 */
class HandlerFactory
{
    /**
     * Create a handler based on method definition
     *
     * @param array $definition
     * @param object|null $context
     * @return MethodHandlerInterface
     */
    public static function create(array $definition, ?object $context = null): MethodHandlerInterface
    {
        if (!isset($definition['function'])) {
            throw new \InvalidArgumentException('Method definition must contain a "function" key');
        }

        $function = $definition['function'];
        $signature = $definition['signature'] ?? null;
        $docstring = $definition['docstring'] ?? '';

        // Check if it's a system method (this.xxx)
        if (is_string($function) && strpos($function, 'this.') === 0) {
            $methodName = substr($function, 5);
            return new SystemMethodHandler($context, $methodName, $signature, $docstring);
        }

        // Check if it's an object method call (Class.method)
        if (is_string($function) && strpos($function, '.') !== false) {
            $parts = explode('.', $function, 2);
            
            if ($context !== null && $parts[0] !== 'this') {
                // Use provided context with method name
                return new ObjectMethodHandler($context, $parts[1], $signature, $docstring);
            } elseif ($parts[0] === 'this') {
                return new SystemMethodHandler($context, $parts[1], $signature, $docstring);
            } else {
                return new ObjectMethodHandler(null, $parts[1], $signature, $docstring);
            }
        }

        // It's a simple function/callable
        return new FunctionHandler($function, $signature, $docstring);
    }
}
