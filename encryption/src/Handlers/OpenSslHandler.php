<?php

declare(strict_types=0);

namespace Kodhe\Framework\Encryption\Handlers;

use Kodhe\Framework\Encryption\Contracts\HandlerInterface;
use Kodhe\Framework\Encryption\Encoding\Base64Encoder;
use Kodhe\Framework\Encryption\Support\CipherAlgorithmResolver;

/**
 * Class OpenSslHandler
 *
 * OpenSSL-based encryption handler implementing authenticated encryption
 * Supports both CBC+HMAC and GCM modes
 *
 * @package     Kodhe\Encryption
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class OpenSslHandler implements HandlerInterface
{
    /**
     * Cipher algorithm (e.g., aes-256, aes-128)
     *
     * @var string
     */
    private string $cipher;

    /**
     * Encryption mode (e.g., cbc, gcm, ctr)
     *
     * @var string
     */
    private string $mode;

    /**
     * Base64 encoder for payload encoding
     *
     * @var Base64Encoder
     */
    private Base64Encoder $encoder;

    /**
     * Cipher algorithm resolver with caching
     *
     * @var CipherAlgorithmResolver
     */
    private CipherAlgorithmResolver $resolver;

    /**
     * Cache for resolved OpenSSL algorithm names
     *
     * @var array<string, string>
     */
    private static array $algorithmCache = [];

    /**
     * Cache for IV lengths per algorithm
     *
     * @var array<string, int>
     */
    private static array $ivLengthCache = [];

    /**
     * Constructor
     *
     * @param string $cipher Cipher algorithm (default: aes-256)
     * @param string $mode Encryption mode (default: cbc)
     */
    public function __construct(string $cipher = 'aes-256', string $mode = 'cbc')
    {
        $this->cipher = $cipher;
        $this->mode = strtolower($mode);
        $this->encoder = new Base64Encoder();
        $this->resolver = new CipherAlgorithmResolver();
    }

    /**
     * Set cipher algorithm
     *
     * @param string $cipher Cipher name
     * @return void
     */
    public function setCipher(string $cipher): void
    {
        $this->cipher = $cipher;
    }

    /**
     * Set encryption mode
     *
     * @param string $mode Encryption mode
     * @return void
     */
    public function setMode(string $mode): void
    {
        $this->mode = strtolower($mode);
    }

    /**
     * Get the resolved OpenSSL algorithm name
     *
     * @return string
     */
    private function getAlgorithm(): string
    {
        $cacheKey = $this->cipher . '-' . $this->mode;

        if (!isset(self::$algorithmCache[$cacheKey])) {
            self::$algorithmCache[$cacheKey] = $this->resolver->resolve($this->cipher, $this->mode);
        }

        return self::$algorithmCache[$cacheKey];
    }

    /**
     * Get the IV length for the current algorithm
     *
     * @return int
     */
    private function getIvLength(): int
    {
        $algorithm = $this->getAlgorithm();

        if (!isset(self::$ivLengthCache[$algorithm])) {
            $ivLength = openssl_cipher_iv_length($algorithm);
            self::$ivLengthCache[$algorithm] = ($ivLength !== false) ? $ivLength : 0;
        }

        return self::$ivLengthCache[$algorithm];
    }

    /**
     * Check if current mode is authenticated (GCM, CCM)
     *
     * @return bool
     */
    private function isAuthenticatedMode(): bool
    {
        return $this->resolver->isAuthenticatedMode($this->mode);
    }

    /**
     * Encrypt data using OpenSSL
     *
     * @param string $data Input data
     * @param string $key Encryption key
     * @param bool $raw Return raw binary instead of base64
     * @param string $hmacDigest HMAC digest algorithm (for CBC mode)
     * @param string|null $hmacKey HMAC key (null to derive from encryption key)
     * @return string|false Encrypted data or FALSE on failure
     */
    public function encrypt(
        string $data,
        string $key,
        bool $raw = false,
        string $hmacDigest = 'SHA512',
        ?string $hmacKey = null
    ) {
        $algorithm = $this->getAlgorithm();
        $ivLength = $this->getIvLength();

        // Generate random IV
        $iv = ($ivLength > 0) ? random_bytes($ivLength) : '';

        // Handle authenticated encryption modes (GCM, CCM)
        if ($this->isAuthenticatedMode()) {
            $tag = '';
            $ciphertext = openssl_encrypt(
                $data,
                $algorithm,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($ciphertext === false) {
                return false;
            }

            // Combine: IV + TAG + Ciphertext
            $payload = $iv . $tag . $ciphertext;
        } else {
            // CBC/CTR mode with HMAC for authentication
            $ciphertext = openssl_encrypt(
                $data,
                $algorithm,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($ciphertext === false) {
                return false;
            }

            // Generate HMAC for integrity verification
            $actualHmacKey = $hmacKey ?? $key;
            $hmac = hash_hmac(strtolower($hmacDigest), $iv . $ciphertext, $actualHmacKey, true);

            // Combine: IV + HMAC + Ciphertext
            $payload = $iv . $hmac . $ciphertext;
        }

        // Return raw binary or base64 encoded
        return $raw ? $payload : $this->encoder->encode($payload);
    }

    /**
     * Decrypt data using OpenSSL
     *
     * @param string $data Encrypted data
     * @param string $key Encryption key
     * @param bool $raw Data is raw binary instead of base64
     * @param string $hmacDigest HMAC digest algorithm (for CBC mode)
     * @param string|null $hmacKey HMAC key (null to derive from encryption key)
     * @return string|false Decrypted data or FALSE on failure
     */
    public function decrypt(
        string $data,
        string $key,
        bool $raw = false,
        string $hmacDigest = 'SHA512',
        ?string $hmacKey = null
    ) {
        // Decode from base64 if not raw
        $payload = $raw ? $data : $this->encoder->decode($data);
        if ($payload === false || strlen($payload) === 0) {
            return false;
        }

        $algorithm = $this->getAlgorithm();
        $ivLength = $this->getIvLength();

        // Extract IV from payload
        if (strlen($payload) <= $ivLength) {
            return false;
        }

        $iv = substr($payload, 0, $ivLength);

        // Handle authenticated encryption modes (GCM, CCM)
        if ($this->isAuthenticatedMode()) {
            // Extract tag (16 bytes for GCM)
            $tagLength = 16;
            if (strlen($payload) <= $ivLength + $tagLength) {
                return false;
            }

            $tag = substr($payload, $ivLength, $tagLength);
            $ciphertext = substr($payload, $ivLength + $tagLength);

            return openssl_decrypt(
                $ciphertext,
                $algorithm,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );
        }

        // CBC/CTR mode with HMAC verification
        $hmacLength = strlen(hash(strtolower($hmacDigest), '', true));
        if (strlen($payload) <= $ivLength + $hmacLength) {
            return false;
        }

        // Extract HMAC and ciphertext
        $storedHmac = substr($payload, $ivLength, $hmacLength);
        $ciphertext = substr($payload, $ivLength + $hmacLength);

        // Verify HMAC (time-safe comparison)
        $actualHmacKey = $hmacKey ?? $key;
        $calculatedHmac = hash_hmac(strtolower($hmacDigest), $iv . $ciphertext, $actualHmacKey, true);

        if (!hash_equals($storedHmac, $calculatedHmac)) {
            return false;
        }

        return openssl_decrypt(
            $ciphertext,
            $algorithm,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }
}
