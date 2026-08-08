<?php

declare(strict_types=1);

namespace Kodhe\Framework\Encryption\Support;

/**
 * Class CipherAlgorithmResolver
 *
 * Resolves cipher and mode combinations to OpenSSL algorithm names
 * with caching for performance optimization
 *
 * @package     Kodhe\Encryption
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class CipherAlgorithmResolver
{
    /**
     * Cache for resolved cipher algorithms
     *
     * @var array<string, string>
     */
    private static array $cache = [];

    /**
     * Cipher aliases between MCrypt and OpenSSL naming conventions
     *
     * @var array<string, array<string, string>>
     */
    private array $aliases = [
        'mcrypt' => [
            'aes-128' => 'rijndael-128',
            'aes-192' => 'rijndael-128',
            'aes-256' => 'rijndael-128',
            'des3-ede3' => 'tripledes',
            'bf' => 'blowfish',
            'cast5' => 'cast-128',
            'rc4' => 'arcfour',
            'rc4-40' => 'arcfour',
        ],
        'openssl' => [
            'rijndael-128' => 'aes-128',
            'tripledes' => 'des-ede3',
            'blowfish' => 'bf',
            'cast-128' => 'cast5',
            'arcfour' => 'rc4-40',
            'rc4' => 'rc4-40',
        ],
    ];

    /**
     * Available modes for OpenSSL
     *
     * @var array<string, string>
     */
    private array $modes = [
        'cbc' => 'cbc',
        'ecb' => 'ecb',
        'ofb' => 'ofb',
        'cfb' => 'cfb',
        'cfb8' => 'cfb8',
        'ctr' => 'ctr',
        'stream' => '',
        'xts' => 'xts',
        'gcm' => 'gcm',
    ];

    /**
     * Resolve cipher and mode to OpenSSL algorithm name
     *
     * @param string $cipher Cipher name (e.g., aes-256, aes-128)
     * @param string $mode Encryption mode (e.g., cbc, gcm, ctr)
     * @return string OpenSSL algorithm name
     */
    public function resolve(string $cipher, string $mode): string
    {
        $cacheKey = $cipher . '-' . $mode;

        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        // Apply cipher alias if needed
        $cipher = $this->resolveAlias($cipher);

        // Handle stream mode (no suffix)
        if ($mode === 'stream') {
            $algorithm = $cipher;
        } else {
            $algorithm = $cipher . '-' . $mode;
        }

        // Cache the result
        self::$cache[$cacheKey] = $algorithm;

        return $algorithm;
    }

    /**
     * Resolve cipher alias to OpenSSL equivalent
     *
     * @param string $cipher Cipher name
     * @return string Resolved cipher name
     */
    public function resolveAlias(string $cipher): string
    {
        // Check openssl aliases first
        if (isset($this->aliases['openssl'][$cipher])) {
            return $this->aliases['openssl'][$cipher];
        }

        // Check mcrypt aliases
        if (isset($this->aliases['mcrypt'][$cipher])) {
            $aliased = $this->aliases['mcrypt'][$cipher];
            // Recursively resolve if needed
            if (isset($this->aliases['openssl'][$aliased])) {
                return $this->aliases['openssl'][$aliased];
            }
            return $aliased;
        }

        return $cipher;
    }

    /**
     * Get supported modes
     *
     * @return array<string, string>
     */
    public function getModes(): array
    {
        return $this->modes;
    }

    /**
     * Clear the cache (useful for testing)
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }

    /**
     * Check if a cipher mode is authenticated (GCM, CCM, etc.)
     *
     * @param string $mode Encryption mode
     * @return bool True if authenticated encryption mode
     */
    public function isAuthenticatedMode(string $mode): bool
    {
        return in_array(strtolower($mode), ['gcm', 'ccm'], true);
    }
}
