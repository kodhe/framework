<?php

namespace Kodhe\Framework\View\Theme;

/**
 * Class Theme
 *
 * @package Kodhe\Framework\View\Theme
 */
class Theme
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * Create a new Theme instance
     *
     * @param string $name
     * @param string $path
     * @param array $config
     */
    public function __construct(string $name, string $path, array $config = [])
    {
        $this->name = $name;
        $this->path = rtrim($path, DIRECTORY_SEPARATOR);
        $this->config = $config;
    }

    /**
     * Get theme name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get theme path
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get theme config
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function getConfig(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->config;
        }

        return $this->config[$key] ?? $default;
    }

    /**
     * Check if theme has config key
     *
     * @param string $key
     * @return bool
     */
    public function hasConfig(string $key): bool
    {
        return isset($this->config[$key]);
    }
}
