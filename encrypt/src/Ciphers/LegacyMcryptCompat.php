<?php

namespace Kodhe\Encrypt\Ciphers;

use Kodhe\Encrypt\Contracts\CipherInterface;

/**
 * Class LegacyMcryptCompat
 *
 * Kompatibilitas untuk membaca data yang dienkripsi dengan mcrypt (legacy CI3)
 * DEPRECATED: Hanya untuk migrasi data lama, jangan gunakan untuk enkripsi baru
 *
 * @package     Kodhe\Encrypt\Ciphers
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 * @deprecated  Gunakan OpenSslCipher untuk enkripsi baru
 */
class LegacyMcryptCompat implements CipherInterface
{
    /**
     * @var string Algoritma cipher (hanya untuk kompatibilitas API)
     */
    private $algorithm = 'rijndael-128';

    /**
     * {@inheritdoc}
     * @deprecated
     */
    public function encrypt(string $data, string $key): string
    {
        // Tidak mendukung enkripsi baru, lempar exception atau return false
        throw new \RuntimeException(
            'LegacyMcryptCompat tidak mendukung enkripsi. ' .
            'Gunakan OpenSslCipher untuk enkripsi baru.'
        );
    }

    /**
     * {@inheritdoc}
     * Decode data legacy CI3 Encrypt library
     * Format lama: base64(MCrypt_iv + MCrypt_ciphertext)
     */
    public function decrypt(string $encoded, string $key)
    {
        // Decode base64
        $data = base64_decode($encoded);

        if ($data === false) {
            return false;
        }

        // CI3 legacy menggunakan format: IV (16 bytes) + ciphertext
        $ivSize = 16; // RIJNDAEL_128 block size

        if (strlen($data) <= $ivSize) {
            return false;
        }

        $iv = substr($data, 0, $ivSize);
        $ciphertext = substr($data, $ivSize);

        // Coba decrypt dengan OpenSSL (karena mcrypt sudah dihapus dari PHP 7.2+)
        // Mode ECB digunakan oleh default CI3 legacy
        $decrypted = openssl_decrypt(
            $ciphertext,
            'aes-128-ecb',
            substr($key, 0, 16), // CI3 legacy memotong key jadi 16/32 bytes
            OPENSSL_RAW_DATA
        );

        // Jika gagal dengan ECB, coba CBC dengan IV
        if ($decrypted === false) {
            $decrypted = openssl_decrypt(
                $ciphertext,
                'aes-128-cbc',
                substr($key, 0, 16),
                OPENSSL_RAW_DATA,
                $iv
            );
        }

        return $decrypted;
    }

    /**
     * {@inheritdoc}
     * @deprecated
     */
    public function setAlgorithm(string $algorithm): void
    {
        $this->algorithm = $algorithm;
    }

    /**
     * {@inheritdoc}
     * @deprecated
     */
    public function setMode(string $mode): void
    {
        // Tidak melakukan apa-apa, hanya untuk kompatibilitas API
    }

    /**
     * Deteksi apakah string adalah format legacy CI3
     *
     * @param string $encoded String terenkripsi
     * @return bool True jika format legacy
     */
    public static function isLegacyFormat(string $encoded): bool
    {
        $data = base64_decode($encoded);

        if ($data === false || strlen($data) < 16) {
            return false;
        }

        // Format legacy CI3 biasanya memiliki panjang tertentu
        // dan tidak memiliki HMAC (32 bytes) seperti format baru
        $decodedLength = strlen($data);

        // Format baru: IV (16) + HMAC (32) + Ciphertext
        // Format lama: IV (16) + Ciphertext saja
        // Jika panjang < 48 bytes, kemungkinan besar format lama
        return $decodedLength < 48;
    }
}
