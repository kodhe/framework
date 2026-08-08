<?php

namespace Kodhe\Email\Traits;

/**
 * Trait for Configuration Handling
 *
 * Note: The using class must provide a protected $config property.
 *
 * @package     Kodhe\Email
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
trait ConfigurableTrait
{
    /**
     * Set configuration
     *
     * @param array $config
     * @return self
     */
    public function setConfig(array $config): self
    {
        if (!isset($this->config) || !is_array($this->config)) {
            $this->config = [];
        }
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * Get configuration value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Check if configuration key exists
     *
     * @param string $key
     * @return bool
     */
    public function hasConfig(string $key): bool
    {
        return isset($this->config[$key]);
    }

    /**
     * Get all configuration
     *
     * @return array
     */
    public function getAllConfig(): array
    {
        return $this->config;
    }
}
