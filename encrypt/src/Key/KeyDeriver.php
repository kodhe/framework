<?php

namespace Kodhe\Framework\Encrypt\Key;

/**
 * Class KeyDeriver
 *
 * Derivasi kunci enkripsi dari password/key string menggunakan HKDF/PBKDF2
 *
 * @package     Kodhe\Encrypt\Key
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class KeyDeriver
{
    /**
     * @var int Panjang key yang akan di-derive (32 bytes untuk AES-256)
     */
    private $keyLength = 32;

    /**
     * @var string Algoritma hash untuk derivasi
     */
    private $hashAlgorithm = 'sha256';

    /**
     * Cache hasil derivasi per request
     *
     * @var array
     */
    private static $cache = [];

    /**
     * Derive key dari password/string input
     *
     * @param string $password Password atau encryption key dari config
     * @param string $salt     Salt opsional (jika tidak ada, gunakan default)
     * @return string          Key yang sudah di-derive (binary safe)
     */
    public function derive(string $password, string $salt = 'kodhe-encrypt-salt'): string
    {
        $cacheKey = hash('sha256', $password . $salt);

        // Return dari cache jika sudah pernah di-derive
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $derivedKey = $this->deriveKey($password, $salt);

        // Cache hasil derivasi
        self::$cache[$cacheKey] = $derivedKey;

        return $derivedKey;
    }

    /**
     * Derive key menggunakan HKDF (HMAC-based Key Derivation Function)
     * Tersedia di PHP 7.0+
     *
     * @param string $password Password input
     * @param string $salt     Salt untuk derivasi
     * @return string          Key yang sudah di-derive
     */
    private function deriveKey(string $password, string $salt): string
    {
        // Gunakan HKDF jika tersedia (PHP 7.0+)
        if (function_exists('hash_hkdf')) {
            return hash_hkdf(
                $this->hashAlgorithm,
                $password,
                $this->keyLength,
                $salt,
                'encryption-key'
            );
        }

        // Fallback ke PBKDF2 dengan hash() untuk PHP versi lama
        return hash_pbkdf2(
            $this->hashAlgorithm,
            $password,
            $salt,
            10000, // Iterations
            $this->keyLength,
            true // Raw binary output
        );
    }

    /**
     * Set panjang key yang akan di-derive
     *
     * @param int $length Panjang key dalam bytes
     * @return self
     */
    public function setKeyLength(int $length): self
    {
        $this->keyLength = $length;
        return $this;
    }

    /**
     * Set algoritma hash untuk derivasi
     *
     * @param string $algorithm Nama algoritma hash (sha256, sha512, dll)
     * @return self
     */
    public function setHashAlgorithm(string $algorithm): self
    {
        if (!in_array($algorithm, hash_algos())) {
            throw new \InvalidArgumentException("Hash algorithm '{$algorithm}' not supported");
        }

        $this->hashAlgorithm = $algorithm;
        return $this;
    }

    /**
     * Clear cache derivasi key
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
