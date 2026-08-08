<?php

declare(strict_types=0);

namespace Kodhe\Framework\Cart\Storage;

use Kodhe\Framework\Cart\Contracts\CartStorageInterface;

/**
 * Class SessionStorage
 * 
 * Stores cart data in PHP session.
 * Compatible with CodeIgniter 3 session driver pattern.
 * 
 * @package Kodhe\Cart\Storage
 */
class SessionStorage implements CartStorageInterface
{
    /**
     * Session key for cart data
     */
    private const SESSION_KEY = 'cart_contents';

    /**
     * @var object CodeIgniter instance
     */
    protected $CI;

    /**
     * Cached cart data to avoid repeated session reads
     */
    protected ?array $cache = null;

    /**
     * Constructor
     *
     * @param object|null $ci CodeIgniter instance (for backward compatibility)
     */
    public function __construct($ci = null)
    {
        $this->CI = $ci ?? kodhe();
        
        // Ensure session is loaded
        if (!isset($this->CI->session)) {
            $this->CI->load->driver('session');
        }
        
        // Load initial data into cache
        $this->cache = $this->CI->session->userdata(self::SESSION_KEY);
    }

    /**
     * Load cart data from session
     *
     * @return array|null
     */
    public function load(): ?array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $data = $this->CI->session->userdata(self::SESSION_KEY);
        $this->cache = is_array($data) ? $data : null;
        
        return $this->cache;
    }

    /**
     * Save cart data to session
     *
     * @param array $data
     * @return bool
     */
    public function save(array $data): bool
    {
        $this->cache = $data;
        
        // If cart is empty or has only totals, remove from session
        if (count($data) <= 2) {
            return $this->delete();
        }

        $result = $this->CI->session->set_userdata([self::SESSION_KEY => $data]);
        return $result !== false;
    }

    /**
     * Delete cart data from session
     *
     * @return bool
     */
    public function delete(): bool
    {
        $this->cache = null;
        $this->CI->session->unset_userdata(self::SESSION_KEY);
        return true;
    }

    /**
     * Check if cart data exists in session
     *
     * @return bool
     */
    public function exists(): bool
    {
        if ($this->cache !== null) {
            return count($this->cache) > 2;
        }

        $data = $this->CI->session->userdata(self::SESSION_KEY);
        return is_array($data) && count($data) > 2;
    }

    /**
     * Clear the internal cache (useful for testing)
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->cache = null;
    }

    /**
     * Get cached data (for testing/debugging)
     *
     * @return array|null
     */
    public function getCache(): ?array
    {
        return $this->cache;
    }
}
