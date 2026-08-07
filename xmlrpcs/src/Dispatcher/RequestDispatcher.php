<?php

declare(strict_types=1);

namespace Kodhe\Xmlrpcs\Dispatcher;

use Kodhe\Xmlrpcs\Contracts\DispatcherInterface;
use Kodhe\Xmlrpcs\Contracts\MethodRegistryInterface;
use Kodhe\Xmlrpcs\Contracts\MethodHandlerInterface;
use Kodhe\Xmlrpcs\Factory\HandlerFactory;
use Kodhe\Xmlrpcs\Exceptions\UnknownMethodException;
use Kodhe\Xmlrpcs\Exceptions\IncorrectParamsException;

/**
 * Dispatcher for XML-RPC method calls
 */
class RequestDispatcher implements DispatcherInterface
{
    /**
     * Method registry
     *
     * @var MethodRegistryInterface
     */
    protected MethodRegistryInterface $registry;

    /**
     * Object context for method calls
     *
     * @var object|null
     */
    protected ?object $objectContext = null;

    /**
     * Cache of created handlers
     *
     * @var array
     */
    protected array $handlerCache = [];

    /**
     * Debug mode
     *
     * @var bool
     */
    protected bool $debug = false;

    /**
     * Constructor
     *
     * @param MethodRegistryInterface $registry
     */
    public function __construct(MethodRegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Dispatch a method call
     *
     * @param string $methodName
     * @param array $params
     * @return mixed
     */
    public function dispatch(string $methodName, array $params): mixed
    {
        // Check if method exists
        if (!$this->registry->has($methodName)) {
            throw new UnknownMethodException("Method '{$methodName}' is not available");
        }

        // Get or create handler
        $handler = $this->getHandler($methodName);

        // Validate signature if present
        $this->validateSignature($handler, $params);

        // Execute the handler
        return $handler->execute($params);
    }

    /**
     * Set the object context for method calls
     *
     * @param object|null $object
     * @return void
     */
    public function setObjectContext(?object $object): void
    {
        $this->objectContext = $object;
        // Clear handler cache when context changes
        $this->handlerCache = [];
    }

    /**
     * Get the object context
     *
     * @return object|null
     */
    public function getObjectContext(): ?object
    {
        return $this->objectContext;
    }

    /**
     * Set debug mode
     *
     * @param bool $debug
     * @return void
     */
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    /**
     * Check if debug mode is enabled
     *
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Get or create a handler for a method
     *
     * @param string $methodName
     * @return MethodHandlerInterface
     */
    protected function getHandler(string $methodName): MethodHandlerInterface
    {
        // Return cached handler if available
        if (isset($this->handlerCache[$methodName])) {
            return $this->handlerCache[$methodName];
        }

        // Get method definition
        $definition = $this->registry->get($methodName);
        
        if ($definition === null) {
            throw new UnknownMethodException("Method '{$methodName}' not found in registry");
        }

        // Create handler using factory
        $handler = HandlerFactory::create($definition, $this->objectContext);
        
        // Cache the handler
        $this->handlerCache[$methodName] = $handler;

        return $handler;
    }

    /**
     * Validate parameters against method signature
     *
     * @param MethodHandlerInterface $handler
     * @param array $params
     * @return void
     */
    protected function validateSignature(MethodHandlerInterface $handler, array $params): void
    {
        $signature = $handler->getSignature();
        
        if ($signature === null || count($signature) === 0) {
            return; // No signature to validate
        }

        // Check each signature option
        foreach ($signature as $sig) {
            if (count($sig) === count($params) + 1) {
                // This signature matches parameter count
                $valid = true;
                
                for ($i = 0, $c = count($params); $i < $c; $i++) {
                    $param = $params[$i];
                    $expectedType = $sig[$i + 1];
                    
                    // Get actual type
                    $actualType = $this->getParamType($param);
                    
                    if ($actualType !== $expectedType) {
                        $valid = false;
                        break;
                    }
                }
                
                if ($valid) {
                    return; // Valid signature found
                }
            }
        }

        // If we get here, no valid signature was found
        throw new IncorrectParamsException('Parameters do not match method signature');
    }

    /**
     * Get the XML-RPC type of a parameter
     *
     * @param mixed $param
     * @return string
     */
    protected function getParamType($param): string
    {
        if (is_scalar($param)) {
            if (is_bool($param)) {
                return 'boolean';
            }
            if (is_int($param)) {
                return 'int';
            }
            if (is_float($param)) {
                return 'double';
            }
            return 'string';
        }
        
        if (is_array($param)) {
            // Check if it's a struct or array
            if (array_keys($param) === range(0, count($param) - 1)) {
                return 'array';
            }
            return 'struct';
        }
        
        if (is_object($param)) {
            return 'struct';
        }

        return 'string';
    }
}
