<?php namespace Kodhe\Framework\View\Engine;

use Exception;

class EngineFactory
{
    protected static $engines = [];
    protected static $defaultEngine = 'blade';
    
    /**
     * Register template engine
     */
    public static function register($name, $engineClass, $config = [])
    {
        self::$engines[$name] = [
            'class' => $engineClass,
            'config' => $config
        ];
    }
    
    /**
     * Get template engine instance
     */
    public static function make($engine = null, $config = [])
    {
        if ($engine === null) {
            $engine = self::$defaultEngine;
        }
        
        if (!isset(self::$engines[$engine])) {
            throw new Exception("Template engine '{$engine}' not registered");
        }
        
        $engineConfig = self::$engines[$engine];
        
        // Merge config
        $finalConfig = array_merge($engineConfig['config'], $config);
        
        // Check if class exists
        if (!class_exists($engineConfig['class'])) {
            throw new Exception("Template engine class '{$engineConfig['class']}' not found");
        }
        
        // Create instance
        return new $engineConfig['class']($finalConfig);
    }
    
    /**
     * Set default engine
     */
    public static function setDefault($engine)
    {
        if (isset(self::$engines[$engine])) {
            self::$defaultEngine = $engine;
        }
    }
    
    /**
     * Get all registered engines
     */
    public static function getEngines()
    {
        return array_keys(self::$engines);
    }
    
    /**
     * Check if engine is registered
     */
    public static function hasEngine($engine)
    {
        return isset(self::$engines[$engine]);
    }
    
    /**
     * Unregister engine
     */
    public static function unregister($engine)
    {
        if (isset(self::$engines[$engine])) {
            unset(self::$engines[$engine]);
        }
    }
    
    /**
     * Clear all registered engines
     */
    public static function clear()
    {
        self::$engines = [];
    }
}