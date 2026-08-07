<?php

namespace Kodhe\Driver\Traits;

/**
 * Trait ConfigurableTrait
 *
 * Trait untuk handling konfigurasi pada driver dan library.
 * Menyediakan method untuk initialize, set, dan get configuration.
 *
 * @package     Kodhe\Driver\Traits
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
trait ConfigurableTrait
{
    /**
     * Configuration array
     *
     * @var array
     */
    protected $config = [];

    /**
     * Initialize configuration
     *
     * @param array $config Configuration array
     * @return self
     */
    public function initialize(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * Set configuration value
     *
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @return self
     */
    public function setConfig(string $key, $value): self
    {
        $this->config[$key] = $value;
        return $this;
    }

    /**
     * Get configuration value
     *
     * @param string $key Configuration key
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value or default
     */
    public function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Get all configuration
     *
     * @return array All configuration
     */
    public function getAllConfig(): array
    {
        return $this->config;
    }

    /**
     * Check if configuration key exists
     *
     * @param string $key Configuration key
     * @return bool True if exists, false if not
     */
    public function hasConfig(string $key): bool
    {
        return isset($this->config[$key]);
    }

    /**
     * Remove configuration key
     *
     * @param string $key Configuration key
     * @return self
     */
    public function removeConfig(string $key): self
    {
        unset($this->config[$key]);
        return $this;
    }

    /**
     * Clear all configuration
     *
     * @return self
     */
    public function clearConfig(): self
    {
        $this->config = [];
        return $this;
    }
}
