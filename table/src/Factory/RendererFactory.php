<?php

declare(strict_types=1);

namespace Kodhe\Table\Factory;

use Kodhe\Table\Contracts\RendererInterface;
use Kodhe\Table\Renderers\HtmlRenderer;
use Kodhe\Table\Renderers\PlainTextRenderer;
use InvalidArgumentException;

/**
 * Factory for creating renderer instances
 */
class RendererFactory
{
    /**
     * @var array Registered renderers
     */
    private static array $renderers = [
        'html' => HtmlRenderer::class,
        'plain' => PlainTextRenderer::class,
        'text' => PlainTextRenderer::class,
    ];

    /**
     * Create a renderer instance
     *
     * @param string $type
     * @return RendererInterface
     * @throws InvalidArgumentException
     */
    public static function create(string $type): RendererInterface
    {
        $type = strtolower($type);
        
        if (!isset(self::$renderers[$type])) {
            throw new InvalidArgumentException("Unknown renderer type: {$type}");
        }

        $class = self::$renderers[$type];
        return new $class();
    }

    /**
     * Register a custom renderer
     *
     * @param string $type
     * @param string $class
     * @return void
     */
    public static function register(string $type, string $class): void
    {
        if (!is_subclass_of($class, RendererInterface::class)) {
            throw new InvalidArgumentException(
                "Renderer class must implement " . RendererInterface::class
            );
        }
        self::$renderers[strtolower($type)] = $class;
    }

    /**
     * Get all registered renderer types
     *
     * @return array
     */
    public static function getTypes(): array
    {
        return array_keys(self::$renderers);
    }

    /**
     * Check if a renderer type is registered
     *
     * @param string $type
     * @return bool
     */
    public static function hasType(string $type): bool
    {
        return isset(self::$renderers[strtolower($type)]);
    }
}
