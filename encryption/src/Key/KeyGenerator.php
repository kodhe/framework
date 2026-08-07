<?php

declare(strict_types=1);

namespace Kodhe\Encryption\Key;

/**
 * Class KeyGenerator
 *
 * Generates cryptographically secure random keys
 *
 * @package     Kodhe\Encryption
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class KeyGenerator
{
    /**
     * Generate a cryptographically secure random key
     *
     * @param int $length Key length in bytes
     * @return string|false Random key or FALSE on failure
     */
    public function generate(int $length)
    {
        if (function_exists('random_bytes')) {
            try {
                return random_bytes($length);
            } catch (\Exception $e) {
                error_log('KeyGenerator: random_bytes failed - ' . $e->getMessage());
                return false;
            }
        }

        // Fallback to OpenSSL
        if (function_exists('openssl_random_pseudo_bytes')) {
            $isSecure = null;
            $key = openssl_random_pseudo_bytes($length, $isSecure);
            if ($isSecure === true) {
                return $key;
            }
        }

        // Last resort: mcrypt (deprecated but for backward compatibility)
        if (defined('MCRYPT_DEV_URANDOM')) {
            return mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
        }

        error_log('KeyGenerator: No secure random source available');
        return false;
    }

    /**
     * Generate a key and return it as hexadecimal string
     *
     * @param int $length Key length in bytes
     * @return string|false Hexadecimal key string or FALSE on failure
     */
    public function generateHex(int $length)
    {
        $key = $this->generate($length);
        return ($key !== false) ? bin2hex($key) : false;
    }

    /**
     * Generate a key and return it as Base64 string
     *
     * @param int $length Key length in bytes
     * @return string|false Base64 key string or FALSE on failure
     */
    public function generateBase64(int $length)
    {
        $key = $this->generate($length);
        return ($key !== false) ? base64_encode($key) : false;
    }
}
