<?php

declare(strict_types=0);

namespace Kodhe\Framework\Calendar\Traits;

/**
 * Trait ConfigurableTrait
 *
 * Provides configuration handling for calendar components
 *
 * @package Kodhe\Calendar\Traits
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
     * Default configuration
     *
     * @var array
     */
    protected $defaultConfig = [
        'template' => null,
        'local_time' => null,
        'start_day' => 'sunday',
        'month_type' => 'long',
        'day_type' => 'abr',
        'locale' => 'en',
        'show_next_prev' => false,
        'next_prev_url' => '',
        'show_other_days' => false,
    ];

    /**
     * Initialize configuration
     *
     * @param array $config
     * @return self
     */
    public function initialize(array $config = []): self
    {
        $this->config = array_merge($this->defaultConfig, $config);
        
        if ($this->config['local_time'] === null) {
            $this->config['local_time'] = time();
        }

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
     * Set configuration value
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function setConfig(string $key, $value): self
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
        return $this->config;
    }
}
