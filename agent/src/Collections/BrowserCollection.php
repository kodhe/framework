<?php

declare(strict_types=1);

namespace Kodhe\Agent\Collections;

/**
 * Class BrowserCollection
 * 
 * Collection of browser data for detection
 * 
 * @package Kodhe\Agent\Collections
 * @author  Your Name
 * @version 2.0.0
 */
class BrowserCollection
{
    /**
     * List of browsers to compare against current user agent
     *
     * @var array
     */
    protected array $browsers = [
        'Opera' => 'Opera',
        'Edge' => 'Edge',
        'Cogent' => 'Cogent',
        'Chrome' => 'Chrome',
        // Safari 537.6 needs to be tested before Safari 537
        'Safari' => 'Safari',
        'MSIE' => 'Internet Explorer',
        'MSIE' => 'Internet Explorer',
        'Trident' => 'Internet Explorer',
        'Firefox' => 'Firefox',
    ];

    /**
     * Get all browsers
     *
     * @return array
     */
    public function all(): array
    {
        return $this->browsers;
    }

    /**
     * Get a specific browser name by key
     *
     * @param string $key Browser key
     * @return string|null
     */
    public function get(string $key): ?string
    {
        return $this->browsers[$key] ?? null;
    }

    /**
     * Check if a browser key exists
     *
     * @param string $key Browser key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->browsers[$key]);
    }

    /**
     * Set custom browsers
     *
     * @param array $browsers Custom browsers array
     * @return void
     */
    public function set(array $browsers): void
    {
        $this->browsers = $browsers;
    }

    /**
     * Add a browser to the collection
     *
     * @param string $key Browser key/pattern
     * @param string $name Browser display name
     * @return void
     */
    public function add(string $key, string $name): void
    {
        $this->browsers[$key] = $name;
    }
}
