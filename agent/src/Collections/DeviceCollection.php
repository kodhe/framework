<?php

declare(strict_types=0);

namespace Kodhe\Framework\Agent\Collections;

/**
 * Class DeviceCollection
 * 
 * Collection of mobile device data for detection
 * 
 * @package Kodhe\Agent\Collections
 * @author  Your Name
 * @version 2.0.0
 */
class DeviceCollection
{
    /**
     * List of mobile browsers to compare against current user agent
     *
     * @var array
     */
    protected array $mobiles = [
        // Smartphones
        'iPhone' => 'iPhone',
        'Android' => 'Android',
        'BlackBerry' => 'BlackBerry',
        'GoogleBot-Mobile' => 'GoogleBot-Mobile',
        
        // Feature phones
        'Nokia' => 'Nokia',
        'SonyEricsson' => 'SonyEricsson',
        'LG' => 'LG',
        'Motorola' => 'Motorola',
        'Samsung' => 'Samsung',
        
        // Tablets
        'iPad' => 'iPad',
        'iPod' => 'iPod',
        'PlayBook' => 'PlayBook',
        'HP Table' => 'HP Table',
        'Kindle' => 'Kindle',
        'Silk' => 'Silk',
        
        // Other mobiles
        'webOS' => 'webOS',
        'Windows Phone' => 'Windows Phone',
    ];

    /**
     * Get all mobile devices
     *
     * @return array
     */
    public function all(): array
    {
        return $this->mobiles;
    }

    /**
     * Get a specific mobile device name by key
     *
     * @param string $key Mobile device key
     * @return string|null
     */
    public function get(string $key): ?string
    {
        return $this->mobiles[$key] ?? null;
    }

    /**
     * Check if a mobile device key exists
     *
     * @param string $key Mobile device key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->mobiles[$key]);
    }

    /**
     * Set custom mobile devices
     *
     * @param array $mobiles Custom mobile devices array
     * @return void
     */
    public function set(array $mobiles): void
    {
        $this->mobiles = $mobiles;
    }

    /**
     * Add a mobile device to the collection
     *
     * @param string $key Mobile device key/pattern
     * @param string $name Mobile device display name
     * @return void
     */
    public function add(string $key, string $name): void
    {
        $this->mobiles[$key] = $name;
    }
}
