<?php

namespace Kodhe\Framework\View\View;

/**
 * Class ViewContext
 *
 * @package Kodhe\Framework\View\View
 */
class ViewContext
{
    /**
     * @var string
     */
    protected $viewName;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var string|null
     */
    protected $theme;

    /**
     * @var string|null
     */
    protected $variant;

    /**
     * @var string
     */
    protected $engine = 'php';

    /**
     * @var bool
     */
    protected $cached = false;

    /**
     * Create a new ViewContext instance
     *
     * @param string $viewName
     * @param array $data
     */
    public function __construct(string $viewName, array $data = [])
    {
        $this->viewName = $viewName;
        $this->data = $data;
    }

    /**
     * Get view name
     *
     * @return string
     */
    public function getViewName(): string
    {
        return $this->viewName;
    }

    /**
     * Set view name
     *
     * @param string $name
     * @return self
     */
    public function setViewName(string $name): self
    {
        $this->viewName = $name;
        return $this;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set data
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
     * Get theme
     *
     * @return string|null
     */
    public function getTheme(): ?string
    {
        return $this->theme;
    }

    /**
     * Set theme
     *
     * @param string|null $theme
     * @return self
     */
    public function setTheme(?string $theme): self
    {
        $this->theme = $theme;
        return $this;
    }

    /**
     * Get variant
     *
     * @return string|null
     */
    public function getVariant(): ?string
    {
        return $this->variant;
    }

    /**
     * Set variant
     *
     * @param string|null $variant
     * @return self
     */
    public function setVariant(?string $variant): self
    {
        $this->variant = $variant;
        return $this;
    }

    /**
     * Get engine
     *
     * @return string
     */
    public function getEngine(): string
    {
        return $this->engine;
    }

    /**
     * Set engine
     *
     * @param string $engine
     * @return self
     */
    public function setEngine(string $engine): self
    {
        $this->engine = $engine;
        return $this;
    }

    /**
     * Check if cached
     *
     * @return bool
     */
    public function isCached(): bool
    {
        return $this->cached;
    }

    /**
     * Set cached
     *
     * @param bool $cached
     * @return self
     */
    public function setCached(bool $cached): self
    {
        $this->cached = $cached;
        return $this;
    }
}
