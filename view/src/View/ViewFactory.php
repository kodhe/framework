<?php

namespace Kodhe\Framework\View\View;

use Kodhe\Framework\View\Contracts\ViewInterface;
use Kodhe\Framework\View\Engine\EngineFactory;
use Kodhe\Framework\View\Support\ViewConfig;

/**
 * Class ViewFactory
 *
 * @package Kodhe\Framework\View\View
 */
class ViewFactory
{
    /**
     * @var EngineFactory
     */
    protected $engineFactory;

    /**
     * @var array
     */
    protected $sharedData = [];

    /**
     * Create a new ViewFactory instance
     *
     * @param EngineFactory|null $engineFactory
     */
    public function __construct(?EngineFactory $engineFactory = null)
    {
        $this->engineFactory = $engineFactory ?? new EngineFactory();
    }

    /**
     * Create a view instance
     *
     * @param string $name
     * @param array $data
     * @param string|null $engine
     * @return ViewInterface
     */
    public function make(string $name, array $data = [], ?string $engine = null): ViewInterface
    {
        $engineName = $engine ?? $this->detectEngine($name);
        $viewEngine = $this->engineFactory->create($engineName);
        
        // Merge shared data
        $data = array_merge($this->sharedData, $data);

        return new View($name, $viewEngine, $data);
    }

    /**
     * Detect engine from view name
     *
     * @param string $name
     * @return string
     */
    protected function detectEngine(string $name): string
    {
        $extension = pathinfo($name, PATHINFO_EXTENSION);

        switch ($extension) {
            case 'blade.php':
                return 'blade';
            case 'twig':
                return 'twig';
            default:
                return 'php';
        }
    }

    /**
     * Share data across all views
     *
     * @param string|array $key
     * @param mixed $value
     * @return self
     */
    public function share($key, $value = null): self
    {
        if (is_array($key)) {
            $this->sharedData = array_merge($this->sharedData, $key);
        } else {
            $this->sharedData[$key] = $value;
        }

        return $this;
    }

    /**
     * Get shared data
     *
     * @return array
     */
    public function getSharedData(): array
    {
        return $this->sharedData;
    }

    /**
     * Clear shared data
     *
     * @return self
     */
    public function clearSharedData(): self
    {
        $this->sharedData = [];
        return $this;
    }

    /**
     * Set the engine factory
     *
     * @param EngineFactory $factory
     * @return self
     */
    public function setEngineFactory(EngineFactory $factory): self
    {
        $this->engineFactory = $factory;
        return $this;
    }

    /**
     * Get the engine factory
     *
     * @return EngineFactory
     */
    public function getEngineFactory(): EngineFactory
    {
        return $this->engineFactory;
    }
}
