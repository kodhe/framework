<?php

declare(strict_types=1);

namespace Kodhe\Encryption\Contracts;

/**
 * Interface HandlerInterface
 *
 * Contract for encryption handlers (OpenSSL, MCrypt, etc.)
 *
 * @package     Kodhe\Encryption
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
interface HandlerInterface
{
    /**
     * Set cipher algorithm
     *
     * @param string $cipher Cipher name
     * @return void
     */
    public function setCipher(string $cipher): void;

    /**
     * Set encryption mode
     *
     * @param string $mode Encryption mode
     * @return void
     */
    public function setMode(string $mode): void;

    /**
     * Encrypt data
     *
     * @param string $data Input data
     * @param string $key Encryption key
     * @param bool $raw Return raw binary instead of base64
     * @param string $hmacDigest HMAC digest algorithm
     * @param string|null $hmacKey HMAC key (null to derive from encryption key)
     * @return string|false Encrypted data or FALSE on failure
     */
    public function encrypt(string $data, string $key, bool $raw = false, string $hmacDigest = 'SHA512', ?string $hmacKey = null);

    /**
     * Decrypt data
     *
     * @param string $data Encrypted data
     * @param string $key Encryption key
     * @param bool $raw Data is raw binary instead of base64
     * @param string $hmacDigest HMAC digest algorithm
     * @param string|null $hmacKey HMAC key (null to derive from encryption key)
     * @return string|false Decrypted data or FALSE on failure
     */
    public function decrypt(string $data, string $key, bool $raw = false, string $hmacDigest = 'SHA512', ?string $hmacKey = null);
}
