<?php

namespace Kodhe\Encrypt\Encoding;

/**
 * Class HexEncoder
 *
 * Encoder/Decoder Hexadecimal untuk data enkripsi
 *
 * @package     Kodhe\Encrypt\Encoding
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class HexEncoder
{
    /**
     * Encode string ke Hexadecimal
     *
     * @param string $data Data binary yang akan di-encode
     * @return string      String hexadecimal
     */
    public function encode(string $data): string
    {
        return bin2hex($data);
    }

    /**
     * Decode string dari Hexadecimal
     *
     * @param string $encoded String hexadecimal yang akan di-decode
     * @return string|false   Data binary atau false jika gagal
     */
    public function decode(string $encoded)
    {
        if (strlen($encoded) % 2 !== 0) {
            return false;
        }

        return hex2bin($encoded);
    }
}
