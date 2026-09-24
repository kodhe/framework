<?php

declare(strict_types=1);

namespace Kodhe\Framework\Encryption\Contracts;

/**
 * Interface EncryptionInterface
 *
 * Main encryption library interface for CodeIgniter 3 compatibility
 *
 * @package     Kodhe\Encryption
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
interface EncryptionInterface
{
    /**
     * Initialize encryption parameters
     *
     * @param array $params Configuration parameters
     * @return self
     */
    public function initialize(array $params): self;

    /**
     * Create a random key
     *
     * @param int $length Output length
     * @return string|false Random key or FALSE on failure
     */
    public function create_key(int $length);

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
    public function hkdf(string $key, string $digest = 'sha512', ?string $salt = null, ?int $length = null, string $info = '');

    /**
     * Encrypt data
     *
     * @param string $data Input data
     * @param array|null $params Input parameters
     * @return string|false Encrypted data or FALSE on failure
     */
    public function encrypt(string $data, ?array $params = null);

    /**
     * Decrypt data
     *
     * @param string $data Encrypted data
     * @param array|null $params Input parameters
     * @return string|false Decrypted data or FALSE on failure
     */
    public function decrypt(string $data, ?array $params = null);
}
