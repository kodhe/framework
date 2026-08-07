<?php

declare(strict_types=1);

namespace Kodhe\Encryption\Encoding;

/**
 * Class HexEncoder
 *
 * Handles hexadecimal encoding and decoding for encrypted payloads
 *
 * @package     Kodhe\Encryption
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class HexEncoder
{
    /**
     * Encode binary data to hexadecimal string
     *
     * @param string $data Binary data to encode
     * @return string Hexadecimal encoded string
     */
    public function encode(string $data): string
    {
        return bin2hex($data);
    }

    /**
     * Decode hexadecimal string to binary data
     *
     * @param string $data Hexadecimal encoded string
     * @return string|false Decoded binary data or FALSE on failure
     */
    public function decode(string $data)
    {
        $decoded = hex2bin($data);
        return ($decoded !== false) ? $decoded : false;
    }

    /**
     * Check if a string is valid hexadecimal
     *
     * @param string $data String to check
     * @return bool True if valid hexadecimal
     */
    public function isValidHex(string $data): bool
    {
        return ctype_xdigit($data) && (strlen($data) % 2 === 0);
    }
}
