<?php

namespace Kodhe\Encrypt;

use Kodhe\Encrypt\Ciphers\OpenSslCipher;
use Kodhe\Encrypt\Ciphers\LegacyMcryptCompat;
use Kodhe\Encrypt\Key\KeyDeriver;
use Kodhe\Encrypt\Exceptions\EncryptException;

/**
 * Class Encrypt
 *
 * Library enkripsi untuk CodeIgniter 3 (LEGACY / DEPRECATED)
 * Dipertahankan untuk backward compatibility API lama.
 * Implementasi internal sudah dimigrasikan dari mcrypt ke OpenSSL.
 *
 * @package     Kodhe\Encrypt
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 * @deprecated  Gunakan CI_Encryption / library Encryption resmi untuk proyek baru
 */
class Encrypt
{
    /**
     * @var OpenSslCipher Cipher untuk enkripsi/dekripsi data baru
     */
    private $cipher;

    /**
     * @var string Kunci enkripsi yang sudah di-derive
     */
    private $key;

    /**
     * @var KeyDeriver Deriver untuk kunci enkripsi
     */
    private $keyDeriver;

    /**
     * @var bool Apakah menampilkan peringatan deprecated
     */
    private $warnDeprecated = true;

    /**
     * Constructor
     *
     * @param array $config Konfigurasi enkripsi
     * @throws EncryptException Jika encryption_key kosong
     */
    public function __construct(array $config = [])
    {
        $rawKey = $config['encryption_key'] ?? '';

        if (empty($rawKey)) {
            log_message('error', 'Encrypt library: encryption_key kosong di config.php');
            throw EncryptException::emptyKey('Encryption key cannot be empty');
        }

        // Inisialisasi key deriver dan derive key
        $this->keyDeriver = new KeyDeriver();
        $this->key = $this->keyDeriver->derive($rawKey);

        // Inisialisasi cipher dengan OpenSSL
        $this->cipher = new OpenSslCipher($this->key);

        // Set algoritma jika ada di config
        if (!empty($config['algorithm'])) {
            $this->cipher->setAlgorithm($config['algorithm']);
        }

        // Set mode jika ada di config
        if (!empty($config['mode'])) {
            $this->cipher->setMode($config['mode']);
        }

        // Tampilkan peringatan deprecated
        if ($this->warnDeprecated) {
            log_message(
                'debug',
                'Encrypt library dimuat. Library ini DEPRECATED, gunakan library Encryption.'
            );
        }
    }

    /**
     * Encode/Enkripsi string
     * Method ini tetap kompatibel dengan API lama CI3
     *
     * @param string $string String yang akan dienkripsi
     * @param string $key    Kunci enkripsi opsional (jika kosong gunakan key dari config)
     * @return string        String terenkripsi (base64 encoded)
     */
    public function encode(string $string, string $key = ''): string
    {
        $resolvedKey = !empty($key) ? $this->keyDeriver->derive($key) : $this->key;
        return $this->cipher->encrypt($string, $resolvedKey);
    }

    /**
     * Decode/Dekripsi string
     * Method ini tetap kompatibel dengan API lama CI3
     * Mendukung format baru (OpenSSL + HMAC) dan format lama (legacy mcrypt)
     *
     * @param string $string String terenkripsi yang akan didekripsi
     * @param string $key    Kunci dekripsi opsional (jika kosong gunakan key dari config)
     * @return string|false  String asli atau false jika gagal
     */
    public function decode(string $string, string $key = '')
    {
        $resolvedKey = !empty($key) ? $this->keyDeriver->derive($key) : $this->key;

        // Deteksi apakah ini format legacy
        if (LegacyMcryptCompat::isLegacyFormat($string)) {
            log_message(
                'warning',
                'Mendeteksi data enkripsi format legacy. Segera migrasikan ke format baru.'
            );

            $legacyCipher = new LegacyMcryptCompat();
            return $legacyCipher->decrypt($string, $resolvedKey);
        }

        // Gunakan cipher baru (OpenSSL)
        return $this->cipher->decrypt($string, $resolvedKey);
    }

    /**
     * Set cipher algorithm
     * Method ini tetap kompatibel dengan API lama CI3
     *
     * @param string $cipher Nama cipher algorithm
     * @return self
     */
    public function set_cipher(string $cipher): self
    {
        $this->cipher->setAlgorithm($cipher);
        return $this;
    }

    /**
     * Set mode operasi
     * Method ini tetap kompatibel dengan API lama CI3
     *
     * @param string $mode Mode operasi (cbc, ctr, cfb, ofb, dll)
     * @return self
     */
    public function set_mode(string $mode): self
    {
        $this->cipher->setMode($mode);
        return $this;
    }

    /**
     * Generate SHA1 hash
     * Method ini tetap kompatibel dengan API lama CI3
     *
     * @param string $str String yang akan di-hash
     * @return string     Hash SHA1
     */
    public function sha1(string $str): string
    {
        return hash('sha1', $str);
    }

    /**
     * Set encryption key
     * Method ini tetap kompatibel dengan API lama CI3
     *
     * @param string $key Kunci enkripsi baru
     * @return self
     */
    public function set_key(string $key = ''): self
    {
        if (!empty($key)) {
            $this->key = $this->keyDeriver->derive($key);
            $this->cipher = new OpenSslCipher($this->key);
        }

        return $this;
    }

    /**
     * Enable/disable warning deprecated
     *
     * @param bool $enable True untuk enable warning
     * @return self
     */
    public function setWarnDeprecated(bool $enable): self
    {
        $this->warnDeprecated = $enable;
        return $this;
    }

    /**
     * Deteksi apakah string menggunakan format legacy
     *
     * @param string $string String terenkripsi
     * @return bool          True jika format legacy
     */
    public function is_legacy_format(string $string): bool
    {
        return LegacyMcryptCompat::isLegacyFormat($string);
    }

    /**
     * Migrasi data dari format legacy ke format baru
     *
     * @param string $oldEncoded String terenkripsi format lama
     * @return string|false      String terenkripsi format baru atau false jika gagal
     */
    public function migrate(string $oldEncoded)
    {
        if (!$this->is_legacy_format($oldEncoded)) {
            // Sudah format baru, tidak perlu migrasi
            return $oldEncoded;
        }

        // Decode dengan format lama
        $plain = $this->decode($oldEncoded);

        if ($plain === false) {
            return false;
        }

        // Re-encode dengan format baru
        return $this->encode($plain);
    }

    /**
     * Encode banyak string sekaligus (batch operation)
     * Untuk performa yang lebih baik saat memproses banyak data
     *
     * @param array  $strings Array string yang akan dienkripsi
     * @param string $key     Kunci enkripsi opsional
     * @return array          Array string terenkripsi
     */
    public function encode_many(array $strings, string $key = ''): array
    {
        $resolvedKey = !empty($key) ? $this->keyDeriver->derive($key) : $this->key;

        return array_map(
            function ($str) use ($resolvedKey) {
                return $this->cipher->encrypt($str, $resolvedKey);
            },
            $strings
        );
    }

    /**
     * Decode banyak string sekaligus (batch operation)
     * Untuk performa yang lebih baik saat memproses banyak data
     *
     * @param array  $encodedStrings Array string terenkripsi
     * @param string $key            Kunci dekripsi opsional
     * @return array                 Array string asli
     */
    public function decode_many(array $encodedStrings, string $key = ''): array
    {
        $resolvedKey = !empty($key) ? $this->keyDeriver->derive($key) : $this->key;

        return array_map(
            function ($encoded) use ($resolvedKey) {
                return $this->cipher->decrypt($encoded, $resolvedKey);
            },
            $encodedStrings
        );
    }
}
