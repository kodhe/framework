<?php

declare(strict_types=1);

namespace Kodhe\Framework\Encryption\Encoding;

/**
 * Class Base64Encoder
 *
 * Handles Base64 encoding and decoding for encrypted payloads
 *
 * @package     Kodhe\Encryption
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class Base64Encoder
{
    /**
     * Encode binary data to Base64 string
     *
     * @param string $data Binary data to encode
     * @return string|false Base64 encoded string or FALSE on failure
     */
    public function encode(string $data)
    {
        return base64_encode($data);
    }

    /**
     * Decode Base64 string to binary data
     *
     * @param string $data Base64 encoded string
     * @return string|false Decoded binary data or FALSE on failure
     */
    public function decode(string $data)
    {
        $decoded = base64_decode($data, true);
        return ($decoded !== false) ? $decoded : false;
    }

    /**
     * Check if a string is valid Base64
     *
     * @param string $data String to check
     * @return bool True if valid Base64
     */
    public function isValidBase64(string $data): bool
    {
        return base64_decode($data, true) !== false;
    }
}
