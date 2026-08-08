<?php

declare(strict_types=1);

namespace Kodhe\Framework\Cart;

/**
 * Simple Session handler for Cart storage
 * 
 * Uses PHP native sessions for cart data persistence
 */
class Session
{
    /**
     * Session data container
     *
     * @var array
     */
    protected static $data = [];

    /**
     * Constructor - starts session if not already started
     */
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        
        // Load cart data from session
        if (isset($_SESSION['cart_contents'])) {
            self::$data['cart_contents'] = $_SESSION['cart_contents'];
        }
    }

    /**
     * Get user data
     *
     * @param string $key Data key
     * @return mixed
     */
    public function userdata($key = '')
    {
        if ($key === '') {
            return self::$data;
        }

        return isset(self::$data[$key]) ? self::$data[$key] : null;
    }

    /**
     * Set user data
     *
     * @param mixed $data Key or associative array
     * @param mixed $value Value if $data is string
     * @return void
     */
    public function set_userdata($data, $value = null)
    {
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                self::$data[$key] = $val;
                $_SESSION[$key] = $val;
            }
        } else {
            self::$data[$data] = $value;
            $_SESSION[$data] = $value;
        }
    }

    /**
     * Unset user data
     *
     * @param string $key Data key
     * @return void
     */
    public function unset_userdata($key)
    {
        unset(self::$data[$key]);
        unset($_SESSION[$key]);
    }
}
