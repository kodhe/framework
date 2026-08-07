<?php

namespace Kodhe\Framework\View\View;

use Kodhe\Framework\View\Contracts\ViewInterface;
use Kodhe\Framework\View\Contracts\ViewEngineInterface;
use Kodhe\Framework\View\Exceptions\ViewNotFoundException;

/**
 * Class View
 *
 * @package Kodhe\Framework\View\View
 */
class View implements ViewInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var ViewEngineInterface
     */
    protected $engine;

    /**
     * @var string|null
     */
    protected $path;

    /**
     * Create a new View instance
     *
     * @param string $name
     * @param ViewEngineInterface $engine
     * @param array $data
     */
    public function __construct(
        string $name,
        ViewEngineInterface $engine,
        array $data = []
    ) {
        $this->name = $name;
        $this->engine = $engine;
        $this->data = $data;
    }

    /**
     * Set the view name
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the view name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set view data
     *
     * @param array $data
     * @return self
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get view data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set a single data item
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function set(string $key, $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Get a single data item
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Render the view
     *
     * @param bool $return
     * @return string|void
     */
    public function render(bool $return = true)
    {
        if (!$this->exists()) {
            throw ViewNotFoundException::make($this->name);
        }

        $output = $this->engine->render($this->name, $this->data);

        if ($return) {
            return $output;
        }

        echo $output;
    }

    /**
     * Check if view exists
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->engine->exists($this->name);
    }

    /**
     * Get the engine
     *
     * @return ViewEngineInterface
     */
    public function getEngine(): ViewEngineInterface
    {
        return $this->engine;
    }

    /**
     * Set the engine
     *
     * @param ViewEngineInterface $engine
     * @return self
     */
    public function setEngine(ViewEngineInterface $engine): self
    {
        $this->engine = $engine;
        return $this;
    }
}
