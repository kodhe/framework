<?php

declare(strict_types=0);

namespace Kodhe\Framework\Profiler\Factory;

use Kodhe\Framework\Profiler\Contracts\RendererInterface;
use Kodhe\Framework\Profiler\Renderers\HtmlRenderer;
use Kodhe\Framework\Profiler\Renderers\TextRenderer;

/**
 * Renderer Factory
 * 
 * Creates renderer instances based on type
 */
class RendererFactory
{
    private array $renderers = [
        'html' => HtmlRenderer::class,
        'text' => TextRenderer::class,
    ];

    private object $lang;
    private array $instances = [];

    public function __construct(object $lang)
    {
        $this->lang = $lang;
    }

    /**
     * Create a renderer instance
     */
    public function create(string $type = 'html'): RendererInterface
    {
        if (isset($this->instances[$type])) {
            return $this->instances[$type];
        }

        $rendererClass = $this->renderers[$type] ?? HtmlRenderer::class;

        if (!class_exists($rendererClass)) {
            throw new \RuntimeException("Renderer class {$rendererClass} does not exist");
        }

        $renderer = new $rendererClass();

        if (!$renderer instanceof RendererInterface) {
            throw new \RuntimeException(
                "Renderer {$rendererClass} must implement RendererInterface"
            );
        }

        $renderer->setLanguage($this->lang);
        $this->instances[$type] = $renderer;

        return $renderer;
    }

    /**
     * Register a custom renderer
     */
    public function register(string $type, string $rendererClass): void
    {
        $this->renderers[$type] = $rendererClass;
        unset($this->instances[$type]);
    }

    /**
     * Check if a renderer type is registered
     */
    public function has(string $type): bool
    {
        return isset($this->renderers[$type]);
    }

    /**
     * Get available renderer types
     */
    public function getAvailableTypes(): array
    {
        return array_keys($this->renderers);
    }
}
