<?php

namespace Kodhe\Encrypt\Contracts;

/**
 * Interface CipherInterface
 *
 * Contract untuk algoritma enkripsi/dekripsi
 *
 * @package     Kodhe\Encrypt\Contracts
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
interface CipherInterface
{
    /**
     * Enkripsi data string
     *
     * @param string $data Data yang akan dienkripsi
     * @param string $key  Kunci enkripsi
     * @return string      Data terenkripsi (encoded)
     */
    public function encrypt(string $data, string $key): string;

    /**
     * Dekripsi data string
     *
     * @param string $encoded Data terenkripsi yang akan didekripsi
     * @param string $key     Kunci dekripsi
     * @return string|false   Data asli atau false jika gagal
     */
    public function decrypt(string $encoded, string $key);

    /**
     * Set algoritma cipher
     *
     * @param string $algorithm Nama algoritma (misal: aes-256-cbc)
     * @return void
     */
    public function setAlgorithm(string $algorithm): void;

    /**
     * Set mode operasi
     *
     * @param string $mode Mode operasi
     * @return void
     */
    public function setMode(string $mode): void;
}
