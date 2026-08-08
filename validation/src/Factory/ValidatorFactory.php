<?php

declare(strict_types=0);

namespace Kodhe\Framework\Validation\Factory;

use Kodhe\Framework\Validation\Contracts\ValidatorInterface;
use Kodhe\Framework\Validation\Exceptions\RuleNotFoundException;

/**
 * Validator Factory
 * 
 * Creates and manages validator instances using Factory pattern
 */
class ValidatorFactory
{
    /**
     * Registered validators
     */
    protected static array $validators = [];
    
    /**
     * Validator instances cache for reuse
     */
    protected static array $instances = [];
    
    /**
     * Register a validator class
     * 
     * @param string $name Rule name
     * @param string|callable $validator Validator class or callable
     * @return void
     */
    public static function register(string $name, string|callable $validator): void
    {
        self::$validators[$name] = $validator;
    }
    
    /**
     * Get a validator instance
     * 
     * @param string $name
     * @param mixed $param Optional parameter
     * @return ValidatorInterface
     * @throws RuleNotFoundException
     */
    public static function make(string $name, mixed $param = null): ValidatorInterface
    {
        // Check if we have a cached instance (for rules without parameters)
        $cacheKey = $param === null ? $name : $name . '[' . $param . ']';
        
        if (isset(self::$instances[$cacheKey])) {
            return self::$instances[$cacheKey];
        }
        
        if (!isset(self::$validators[$name])) {
            throw new RuleNotFoundException($name);
        }
        
        $validator = self::$validators[$name];
        
        // If it's a class name, instantiate it
        if (is_string($validator)) {
            $instance = new $validator();
            if ($param !== null && method_exists($instance, 'setParameter')) {
                $instance->setParameter($param);
            }
            self::$instances[$cacheKey] = $instance;
            return $instance;
        }
        
        // If it's a callable, wrap it
        if (is_callable($validator)) {
            $instance = new \Kodhe\Framework\Validation\Validators\CallableValidator($validator);
            self::$instances[$cacheKey] = $instance;
            return $instance;
        }
        
        throw new RuleNotFoundException($name);
    }
    
    /**
     * Check if a validator is registered
     * 
     * @param string $name
     * @return bool
     */
    public static function has(string $name): bool
    {
        return isset(self::$validators[$name]);
    }
    
    /**
     * Clear all cached instances
     * 
     * @return void
     */
    public static function clearCache(): void
    {
        self::$instances = [];
    }
    
    /**
     * Get all registered validators
     * 
     * @return array
     */
    public static function getRegisteredValidators(): array
    {
        return self::$validators;
    }
}
