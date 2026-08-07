<?php

namespace Kodhe\Calendar\Traits;

/**
 * Trait ConfigurableTrait
 *
 * Provides configuration handling for calendar components
 *
 * @package     Kodhe\Calendar
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
    private $config = [];

    /**
     * Default configuration values
     *
     * @var array
     */
    protected $defaults = [
        'start_day'   => 'sunday',
        'month_type'  => 'long',
        'day_type'    => 'abr',
        'locale'      => 'en',
        'template'    => null,
        'show_next_prev' => false,
        'next_prev_url'  => '',
    ];

    /**
     * Initialize with configuration
     *
     * @param array $config
     * @return self
     */
    public function configure(array $config = []): self
    {
        $this->config = array_merge($this->defaults, $config);
        return $this;
    }

    /**
     * Get configuration value
     *
     * @param string $key     Configuration key
     * @param mixed  $default Default value if key not found
     * @return mixed
     */
    public function getConfigValue(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Set configuration value
     *
     * @param string $key   Configuration key
     * @param mixed  $value Configuration value
     * @return self
     */
    public function setConfigValue(string $key, $value): self
    {
        $this->config[$key] = $value;
        return $this;
    }

    /**
     * Get all configuration
     *
     * @return array
     */
    public function getAllConfig(): array
    {
        return array_merge($this->defaults, $this->config);
    }

    /**
     * Reset configuration to defaults
     *
     * @return self
     */
    public function resetConfig(): self
    {
        $this->config = [];
        return $this;
    }
}
