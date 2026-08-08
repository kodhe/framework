<?php

declare(strict_types=0);

namespace Kodhe\Framework\Encryption;

use Kodhe\Framework\Encryption\Contracts\EncryptionInterface;
use Kodhe\Framework\Encryption\Contracts\HandlerInterface;
use Kodhe\Framework\Encryption\Handlers\OpenSslHandler;
use Kodhe\Framework\Encryption\Key\KeyDeriver;
use Kodhe\Framework\Encryption\Key\KeyGenerator;

/**
 * Encryption Library for CodeIgniter 3
 *
 * Provides two-way keyed encryption via PHP's OpenSSL extension.
 * Refactored to follow PSR-4/PSR-12 standards while maintaining
 * full backward compatibility with CodeIgniter 3 API.
 *
 * Features:
 * - Strategy Pattern for encryption modes (CBC+HMAC vs GCM)
 * - Value Object Pattern for encrypted payloads
 * - Dependency Injection for handlers and key derivation
 * - Caching for cipher algorithm resolution (performance)
 * - Support for both raw binary and base64 encoded data
 *
 * @package     Kodhe\Encryption
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 * @link        https://github.com/yourname/encryption
 */
class Encryption implements EncryptionInterface
{
    /**
     * Encryption cipher
     *
     * @var string
     */
    protected string $_cipher = 'aes-128';

    /**
     * Cipher mode
     *
     * @var string
     */
    protected string $_mode = 'cbc';

    /**
     * Encryption handler (OpenSSL, MCrypt, etc.)
     *
     * @var HandlerInterface|null
     */
    protected ?HandlerInterface $_handler = null;

    /**
     * Encryption key
     *
     * @var string
     */
    protected string $_key = '';

    /**
     * Key deriver for HKDF
     *
     * @var KeyDeriver
     */
    protected KeyDeriver $_keyDeriver;

    /**
     * Key generator for create_key()
     *
     * @var KeyGenerator
     */
    protected KeyGenerator $_keyGenerator;

    /**
     * List of supported HMAC algorithms
     * name => digest size pairs
     *
     * @var array<string, int>
     */
    protected array $_digests = [
        'sha224' => 28,
        'sha256' => 32,
        'sha384' => 48,
        'sha512' => 64,
    ];

    /**
     * List of available modes for OpenSSL
     *
     * @var array<string, string>
     */
    protected array $_modes = [
        'openssl' => [
            'cbc' => 'cbc',
            'ecb' => 'ecb',
            'ofb' => 'ofb',
            'cfb' => 'cfb',
            'cfb8' => 'cfb8',
            'ctr' => 'ctr',
            'stream' => '',
            'xts' => 'xts',
            'gcm' => 'gcm',
        ],
    ];

    /**
     * mbstring.func_overload flag
     *
     * @var bool|null
     */
    protected static ?bool $func_overload = null;

    /**
     * Class constructor
     *
     * @param array $params Configuration parameters
     * @return void
     */
    public function __construct(array $params = [])
    {
        // Initialize dependencies
        $this->_keyDeriver = new KeyDeriver();
        $this->_keyGenerator = new KeyGenerator();

        // Set func_overload for byte-safe string operations
        if (self::$func_overload === null) {
            self::$func_overload = (extension_loaded('mbstring') && ini_get('mbstring.func_overload'));
        }

        // Initialize with provided parameters
        $this->initialize($params);

        // Try to load key from config if not set
        if (strlen($this->_key) === 0 && function_exists('config_item')) {
            $key = config_item('encryption_key');
            if ($key !== null && strlen((string) $key) > 0) {
                $this->_key = (string) $key;
            }
        }
    }

    /**
     * Initialize encryption parameters
     *
     * @param array $params Configuration parameters
     * @return EncryptionInterface
     */
    public function initialize(array $params): EncryptionInterface
    {
        // Set cipher if provided
        if (!empty($params['cipher'])) {
            $this->_cipher = strtolower($params['cipher']);
        }

        // Set mode if provided
        if (!empty($params['mode'])) {
            $params['mode'] = strtolower($params['mode']);
            if (isset($this->_modes['openssl'][$params['mode']])) {
                $this->_mode = $this->_modes['openssl'][$params['mode']];
            }
        }

        // Set key if provided
        if (!empty($params['key'])) {
            $this->_key = $params['key'];
        }

        // Create handler with current cipher and mode
        $this->_handler = new OpenSslHandler($this->_cipher, $this->_mode);

        return $this;
    }

    /**
     * Create a random key
     *
     * @param int $length Output length
     * @return string|false Random key or FALSE on failure
     */
    public function create_key($length)
    {
        return $this->_keyGenerator->generate((int) $length);
    }

    /**
     * HKDF - HMAC-based Extract-and-Expand Key Derivation Function
     *
     * @link https://tools.ietf.org/rfc/rfc5869.txt
     * @param string $key Input key
     * @param string $digest A SHA-2 hashing algorithm
     * @param string|null $salt Optional salt
     * @param int|null $length Output length (defaults to the selected digest size)
     * @param string $info Optional context/application-specific info
     * @return string|false A pseudo-random key or FALSE on failure
     */
    public function hkdf(
        string $key,
        string $digest = 'sha512',
        ?string $salt = null,
        ?int $length = null,
        string $info = ''
    ) {
        return $this->_keyDeriver->hkdf($key, $digest, $salt, $length, $info);
    }

    /**
     * Encrypt data
     *
     * @param string $data Input data
     * @param array|null $params Input parameters
     * @return string|false Encrypted data or FALSE on failure
     */
    public function encrypt($data, ?array $params = null)
    {
        // Get merged parameters
        $params = $this->_getParams($params);
        if ($params === false) {
            return false;
        }

        // Derive encryption key if not provided
        if (!isset($params['key']) || empty($params['key'])) {
            if (empty($this->_key)) {
                return false;
            }
            $params['key'] = $this->hkdf(
                $this->_key,
                'sha512',
                null,
                self::strlen($this->_key),
                'encryption'
            );
        }

        // Perform encryption via handler
        $result = $this->_handler->encrypt(
            (string) $data,
            $params['key'],
            !$params['base64'],
            $params['hmac_digest'] ?? 'SHA512',
            $params['hmac_key'] ?? null
        );

        return $result;
    }

    /**
     * Decrypt data
     *
     * @param string $data Encrypted data
     * @param array|null $params Input parameters
     * @return string|false Decrypted data or FALSE on failure
     */
    public function decrypt($data, ?array $params = null)
    {
        // Get merged parameters
        $params = $this->_getParams($params);
        if ($params === false) {
            return false;
        }

        // Handle HMAC verification first (before base64 decode)
        if (isset($params['hmac_digest']) && !empty($params['hmac_digest'])) {
            // The digest_size calculation accounts for base64 encoding
            $digest_size = $params['base64']
                ? $this->_digests[$params['hmac_digest']] * 2
                : $this->_digests[$params['hmac_digest']];

            if (self::strlen($data) <= $digest_size) {
                return false;
            }

            $hmac_input = self::substr($data, 0, $digest_size);
            $data = self::substr($data, $digest_size);

            // Derive HMAC key if not provided
            if (!isset($params['hmac_key']) || empty($params['hmac_key'])) {
                if (empty($this->_key)) {
                    return false;
                }
                $params['hmac_key'] = $this->hkdf(
                    $this->_key,
                    'sha512',
                    null,
                    null,
                    'authentication'
                );
            }

            // Calculate and verify HMAC
            $hmac_check = hash_hmac(
                strtolower($params['hmac_digest']),
                $data,
                $params['hmac_key'],
                !$params['base64']
            );

            // Time-attack-safe comparison
            if (!hash_equals($hmac_input, $hmac_check)) {
                return false;
            }
        }

        // Decode from base64 if needed
        if ($params['base64']) {
            $data = base64_decode($data);
            if ($data === false) {
                return false;
            }
        }

        // Derive encryption key if not provided
        if (!isset($params['key']) || empty($params['key'])) {
            if (empty($this->_key)) {
                return false;
            }
            $params['key'] = $this->hkdf(
                $this->_key,
                'sha512',
                null,
                self::strlen($this->_key),
                'encryption'
            );
        }

        // Perform decryption via handler
        return $this->_handler->decrypt(
            $data,
            $params['key'],
            !$params['base64'],
            $params['hmac_digest'] ?? 'SHA512',
            $params['hmac_key'] ?? null
        );
    }

    /**
     * Get encryption parameters with defaults
     *
     * @param array|null $params Input parameters
     * @return array|false Merged parameters or FALSE on failure
     */
    protected function _getParams(?array $params = null)
    {
        // Return default params if none provided
        if (empty($params)) {
            return [
                'cipher' => $this->_cipher,
                'mode' => $this->_mode,
                'key' => null,
                'base64' => true,
                'hmac_digest' => 'sha512',
                'hmac_key' => null,
            ];
        }

        // Validate required params for custom configuration
        if (!isset($params['cipher'], $params['mode'])) {
            // Use defaults for missing values
            $params['cipher'] = $params['cipher'] ?? $this->_cipher;
            $params['mode'] = $params['mode'] ?? $this->_mode;
        }

        // Normalize mode
        if (isset($params['mode'])) {
            $params['mode'] = strtolower($params['mode']);
            if (!isset($this->_modes['openssl'][$params['mode']])) {
                return false;
            }
            $params['mode'] = $this->_modes['openssl'][$params['mode']];
        }

        // Handle HMAC settings
        if (isset($params['hmac']) && $params['hmac'] === false) {
            $params['hmac_digest'] = null;
            $params['hmac_key'] = null;
        } else {
            $params['hmac_digest'] = isset($params['hmac_digest'])
                ? strtolower($params['hmac_digest'])
                : 'sha512';

            if (!empty($params['hmac_digest']) && !isset($this->_digests[$params['hmac_digest']])) {
                return false;
            }
        }

        // Handle raw_data flag (inverted base64)
        $params['base64'] = isset($params['raw_data']) ? !$params['raw_data'] : true;

        return $params;
    }

    /**
     * Byte-safe strlen()
     *
     * @param string $str
     * @return int
     */
    protected static function strlen(string $str): int
    {
        return (self::$func_overload)
            ? mb_strlen($str, '8bit')
            : strlen($str);
    }

    /**
     * Byte-safe substr()
     *
     * @param string $str
     * @param int $start
     * @param int|null $length
     * @return string
     */
    protected static function substr(string $str, int $start, ?int $length = null): string
    {
        if (self::$func_overload) {
            // mb_substr($str, $start, null, '8bit') returns an empty
            // string on PHP 5.3
            if ($length === null) {
                $length = ($start >= 0 ? self::strlen($str) - $start : -$start);
            }
            return mb_substr($str, $start, $length, '8bit');
        }

        return isset($length)
            ? substr($str, $start, $length)
            : substr($str, $start);
    }

    /**
     * __get() magic method for backward compatibility
     *
     * @param string $key Property name
     * @return mixed
     */
    public function __get(string $key)
    {
        // Because aliases
        if ($key === 'mode') {
            return array_search($this->_mode, $this->_modes['openssl'], true);
        } elseif (in_array($key, ['cipher', 'driver', 'drivers', 'digests'], true)) {
            return $this->{'_' . $key};
        }

        return null;
    }

    /**
     * Get the current cipher
     *
     * @return string
     */
    public function get_cipher(): string
    {
        return $this->_cipher;
    }

    /**
     * Get the current mode
     *
     * @return string
     */
    public function get_mode(): string
    {
        return $this->_mode;
    }

    /**
     * Encrypt multiple items (batch operation)
     *
     * @param array $items Array of data to encrypt
     * @param array|null $params Input parameters
     * @return array|false Array of encrypted data or FALSE on failure
     */
    public function encrypt_many(array $items, ?array $params = null)
    {
        $result = [];
        foreach ($items as $item) {
            $encrypted = $this->encrypt($item, $params);
            if ($encrypted === false) {
                return false;
            }
            $result[] = $encrypted;
        }
        return $result;
    }

    /**
     * Decrypt multiple items (batch operation)
     *
     * @param array $items Array of encrypted data
     * @param array|null $params Input parameters
     * @return array|false Array of decrypted data or FALSE on failure
     */
    public function decrypt_many(array $items, ?array $params = null)
    {
        $result = [];
        foreach ($items as $item) {
            $decrypted = $this->decrypt($item, $params);
            if ($decrypted === false) {
                return false;
            }
            $result[] = $decrypted;
        }
        return $result;
    }
}
