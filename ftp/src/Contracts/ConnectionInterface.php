<?php

namespace Kodhe\Framework\Ftp\Contracts;

/**
 * Interface ConnectionInterface
 *
 * Contract untuk koneksi FTP/SFTP
 *
 * @package     Kodhe\Ftp\Contracts
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
interface ConnectionInterface
{
    /**
     * Buka koneksi ke server
     *
     * @return bool True jika berhasil, false jika gagal
     */
    public function open(): bool;

    /**
     * Dapatkan handle/resource koneksi
     *
     * @return resource|null Handle koneksi
     */
    public function getHandle();

    /**
     * Tutup koneksi
     *
     * @return bool True jika berhasil ditutup
     */
    public function close(): bool;

    /**
     * Cek apakah koneksi masih aktif
     *
     * @return bool True jika terhubung
     */
    public function isConnected(): bool;
}
