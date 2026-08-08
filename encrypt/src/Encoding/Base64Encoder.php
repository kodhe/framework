<?php

namespace Kodhe\Framework\Encrypt\Encoding;

/**
 * Class Base64Encoder
 *
 * Encoder/Decoder Base64 untuk data enkripsi
 *
 * @package     Kodhe\Encrypt\Encoding
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class Base64Encoder
{
    /**
     * Encode string ke Base64
     *
     * @param string $data Data binary yang akan di-encode
     * @return string      String Base64
     */
    public function encode(string $data): string
    {
        return base64_encode($data);
    }

    /**
     * Decode string dari Base64
     *
     * @param string $encoded String Base64 yang akan di-decode
     * @return string|false   Data binary atau false jika gagal
     */
    public function decode(string $encoded)
    {
        return base64_decode($encoded);
    }
}
