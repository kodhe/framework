<?php

namespace Kodhe\Framework\View\Engine;

use Kodhe\Framework\View\Exceptions\EngineException;

/**
 * Class EngineFactory
 *
 * @package Kodhe\Framework\View\Engine
 */
class EngineFactory
{
    /**
     * @var array
     */
    protected $engines = [];

    /**
     * @var array
     */
    protected $defaultPaths = [];

    /**
     * Create a new EngineFactory instance
     *
     * @param array $paths
     */
    public function __construct(array $paths = [])
    {
        $this->defaultPaths = $paths;
        $this->registerDefaults();
    }

    /**
     * Register default engines
     *
     * @return void
     */
    protected function registerDefaults(): void
    {
        $this->register('php', PhpEngine::class);
        $this->register('blade', BladeEngine::class);
        $this->register('twig', TwigEngine::class);
    }

    /**
     * Create an engine instance
     *
     * @param string $name
     * @return EngineInterface
     */
    public function create(string $name): EngineInterface
    {
        if (!$this->has($name)) {
            throw EngineException::notFound($name);
        }

        $class = $this->engines[$name];
        $engine = new $class($this->defaultPaths);

        if (!$engine instanceof EngineInterface) {
            throw EngineException::unsupported($name);
        }

        return $engine;
    }

    /**
     * Register an engine
     *
     * @param string $name
     * @param string $class
     * @return self
     */
    public function register(string $name, string $class): self
    {
        $this->engines[$name] = $class;
        return $this;
    }

    /**
     * Check if engine is registered
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->engines[$name]);
    }

    /**
     * Get all registered engines
     *
     * @return array
     */
    public function getEngines(): array
    {
        return array_keys($this->engines);
    }

    /**
     * Set default view paths
     *
     * @param array $paths
     * @return self
     */
    public function setDefaultPaths(array $paths): self
    {
        $this->defaultPaths = $paths;
        return $this;
    }

    /**
     * Get default view paths
     *
     * @return array
     */
    public function getDefaultPaths(): array
    {
        return $this->defaultPaths;
    }
}
