<?php

namespace Kodhe\Framework\View\Asset;

/**
 * Class Asset
 *
 * @package Kodhe\Framework\View\Asset
 */
class Asset
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * Create a new Asset instance
     *
     * @param string $type
     * @param string $path
     * @param array $attributes
     */
    public function __construct(string $type, string $path, array $attributes = [])
    {
        $this->type = $type;
        $this->path = $path;
        $this->attributes = $attributes;
    }

    /**
     * Get asset type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get asset path
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Set an attribute
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function setAttribute(string $key, $value): self
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Get an attribute
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getAttribute(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Render the asset tag
     *
     * @return string
     */
    public function render(): string
    {
        if ($this->type === 'css') {
            return $this->renderCss();
        }

        if ($this->type === 'js') {
            return $this->renderJs();
        }

        return '';
    }

    /**
     * Render CSS tag
     *
     * @return string
     */
    protected function renderCss(): string
    {
        $attrs = $this->buildAttributes([
            'rel' => 'stylesheet',
            'href' => $this->path
        ]);

        return "<link{$attrs}>" . PHP_EOL;
    }

    /**
     * Render JS tag
     *
     * @return string
     */
    protected function renderJs(): string
    {
        $attrs = $this->buildAttributes([
            'src' => $this->path
        ]);

        return "<script{$attrs}></script>" . PHP_EOL;
    }

    /**
     * Build HTML attributes string
     *
     * @param array $extra
     * @return string
     */
    protected function buildAttributes(array $extra = []): string
    {
        $attributes = array_merge($this->attributes, $extra);
        $parts = [];

        foreach ($attributes as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $parts[] = $key;
                }
            } else {
                $parts[] = sprintf('%s="%s"', $key, htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'));
            }
        }

        return empty($parts) ? '' : ' ' . implode(' ', $parts);
    }
}
