<?php

namespace Kodhe\Framework\Encrypt\Ciphers;

use Kodhe\Framework\Encrypt\Contracts\CipherInterface;
use Kodhe\Framework\Encrypt\Encoding\Base64Encoder;

/**
 * Class OpenSslCipher
 *
 * Implementasi cipher menggunakan OpenSSL dengan AES-256-CBC + HMAC untuk integritas
 *
 * @package     Kodhe\Encrypt\Ciphers
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class OpenSslCipher implements CipherInterface
{
    /**
     * @var string Algoritma enkripsi
     */
    private $algorithm = 'aes-256-cbc';

    /**
     * @var string Kunci enkripsi
     */
    private $key;

    /**
     * @var Base64Encoder Encoder untuk output
     */
    private $encoder;

    /**
     * @var int Panjang IV untuk algoritma saat ini
     */
    private $ivLength;

    /**
     * Constructor
     *
     * @param string $key Kunci enkripsi (sudah di-derive)
     */
    public function __construct(string $key)
    {
        $this->key = $key;
        $this->encoder = new Base64Encoder();
        $this->ivLength = openssl_cipher_iv_length($this->algorithm);
    }

    /**
     * {@inheritdoc}
     */
    public function encrypt(string $data, string $key): string
    {
        // Generate IV yang random dan aman
        $iv = random_bytes($this->ivLength);

        // Enkripsi data
        $ciphertext = openssl_encrypt(
            $data,
            $this->algorithm,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('OpenSSL encryption failed');
        }

        // Buat HMAC untuk integritas data (mencegah tampering)
        $hmac = hash_hmac('sha256', $iv . $ciphertext, $key, true);

        // Gabungkan IV + HMAC + Ciphertext, lalu encode ke base64
        return $this->encoder->encode($iv . $hmac . $ciphertext);
    }

    /**
     * {@inheritdoc}
     */
    public function decrypt(string $encoded, string $key)
    {
        // Decode dari base64 ke binary
        $raw = $this->encoder->decode($encoded);

        if ($raw === false || strlen($raw) < ($this->ivLength + 32)) {
            return false;
        }

        // Pisahkan IV, HMAC, dan Ciphertext
        $iv = substr($raw, 0, $this->ivLength);
        $hmac = substr($raw, $this->ivLength, 32);
        $ciphertext = substr($raw, $this->ivLength + 32);

        // Verifikasi HMAC untuk memastikan data tidak di-tamper
        $calcHmac = hash_hmac('sha256', $iv . $ciphertext, $key, true);

        if (!hash_equals($hmac, $calcHmac)) {
            // Data telah di-tamper atau kunci salah
            return false;
        }

        // Dekripsi data
        $decrypted = openssl_decrypt(
            $ciphertext,
            $this->algorithm,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        return $decrypted;
    }

    /**
     * {@inheritdoc}
     */
    public function setAlgorithm(string $algorithm): void
    {
        if (!in_array($algorithm, openssl_get_cipher_methods())) {
            throw new \InvalidArgumentException("Cipher algorithm '{$algorithm}' not supported");
        }

        $this->algorithm = $algorithm;
        $this->ivLength = openssl_cipher_iv_length($algorithm);
    }

    /**
     * {@inheritdoc}
     */
    public function setMode(string $mode): void
    {
        // Map mode CI3 lama ke algoritma OpenSSL setara
        $modeMap = [
            'cbc' => 'cbc',
            'ctr' => 'ctr',
            'cfb' => 'cfb',
            'ofb' => 'ofb',
            'nofb' => 'ofb',
            'stream' => 'ctr',
        ];

        $opensslMode = $modeMap[strtolower($mode)] ?? 'cbc';

        // Update algorithm dengan mode baru
        $baseAlgo = explode('-', $this->algorithm)[0] . '-' . explode('-', $this->algorithm)[1];
        $newAlgorithm = $baseAlgo . '-' . $opensslMode;

        if (in_array($newAlgorithm, openssl_get_cipher_methods())) {
            $this->algorithm = $newAlgorithm;
            $this->ivLength = openssl_cipher_iv_length($newAlgorithm);
        }
    }
}
