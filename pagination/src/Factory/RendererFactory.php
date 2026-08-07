<?php

declare(strict_types=1);

namespace Kodhe\Pagination\Factory;

use Kodhe\Pagination\Contracts\RendererInterface;
use Kodhe\Pagination\Renderers\DefaultRenderer;
use Kodhe\Pagination\Renderers\BootstrapRenderer;
use Kodhe\Pagination\Renderers\TailwindRenderer;
use InvalidArgumentException;

/**
 * Renderer Factory
 * 
 * Creates renderer instances based on type
 */
class RendererFactory
{
    private static array $renderers = [
        'default' => DefaultRenderer::class,
        'bootstrap' => BootstrapRenderer::class,
        'tailwind' => TailwindRenderer::class,
    ];
    
    /**
     * Create a renderer instance
     * 
     * @param string|RendererInterface $renderer Renderer type or instance
     * @return RendererInterface
     * @throws InvalidArgumentException
     */
    public static function make($renderer = 'default'): RendererInterface
    {
        if ($renderer instanceof RendererInterface) {
            return $renderer;
        }
        
        if (!is_string($renderer)) {
            throw new InvalidArgumentException(
                'Renderer must be a string type or RendererInterface instance'
            );
        }
        
        $type = strtolower($renderer);
        
        if (!isset(self::$renderers[$type])) {
            throw new InvalidArgumentException(
                "Unknown renderer type: {$renderer}. Available types: " . 
                implode(', ', array_keys(self::$renderers))
            );
        }
        
        $class = self::$renderers[$type];
        
        return new $class();
    }
    
    /**
     * Register a custom renderer
     * 
     * @param string $type Renderer type name
     * @param string $class Renderer class name (must implement RendererInterface)
     * @return void
     * @throws InvalidArgumentException
     */
    public static function register(string $type, string $class): void
    {
        if (!is_a($class, RendererInterface::class, true)) {
            throw new InvalidArgumentException(
                "Class {$class} must implement RendererInterface"
            );
        }
        
        self::$renderers[strtolower($type)] = $class;
    }
    
    /**
     * Get all registered renderer types
     * 
     * @return array
     */
    public static function getRegisteredTypes(): array
    {
        return array_keys(self::$renderers);
    }
}
