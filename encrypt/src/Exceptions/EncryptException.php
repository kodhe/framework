<?php

namespace Kodhe\Encrypt\Exceptions;

/**
 * Class EncryptException
 *
 * Exception untuk library Encrypt
 *
 * @package     Kodhe\Encrypt\Exceptions
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class EncryptException extends \RuntimeException
{
    /**
     * Exception untuk key yang kosong atau tidak valid
     *
     * @param string $message Pesan error opsional
     * @return self
     */
    public static function emptyKey(string $message = 'Encryption key is empty'): self
    {
        return new self($message);
    }

    /**
     * Exception untuk cipher algorithm yang tidak didukung
     *
     * @param string $algorithm Nama algoritma yang tidak didukung
     * @return self
     */
    public static function unsupportedCipher(string $algorithm): self
    {
        return new self("Cipher algorithm '{$algorithm}' is not supported");
    }

    /**
     * Exception untuk dekripsi yang gagal
     *
     * @param string $message Pesan error opsional
     * @return self
     */
    public static function decryptionFailed(string $message = 'Decryption failed'): self
    {
        return new self($message);
    }

    /**
     * Exception untuk data yang ter-tamper
     *
     * @param string $message Pesan error opsional
     * @return self
     */
    public static function tamperedData(string $message = 'Data integrity check failed'): self
    {
        return new self($message);
    }
}
