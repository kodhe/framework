<?php

namespace Kodhe\Framework\Ftp\Operations;

use Kodhe\Framework\Ftp\Contracts\ConnectionInterface;

/**
 * Class DirectoryOperations
 *
 * Menangani operasi direktori FTP (mkdir, list_files, delete_dir, changedir)
 *
 * @package     Kodhe\Ftp\Operations
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class DirectoryOperations
{
    /**
     * @var ConnectionInterface Koneksi FTP
     */
    private ConnectionInterface $connection;

    /**
     * Constructor
     *
     * @param ConnectionInterface $connection Koneksi FTP
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Buat direktori baru
     *
     * @param string $path Path direktori yang akan dibuat
     * @param int|null $permissions Permissions chmod (opsional)
     * @return string|false Path direktori yang dibuat, atau false jika gagal
     */
    public function mkdir(string $path, ?int $permissions = null)
    {
        $handle = $this->connection->getHandle();
        $result = @ftp_mkdir($handle, $path);

        // Set permissions jika diminta dan berhasil membuat direktori
        if ($result !== false && $permissions !== null) {
            @ftp_chmod($handle, $permissions, $path);
        }

        return $result;
    }

    /**
     * List file dalam direktori
     *
     * @param string $path Path direktori
     * @return array|false Array daftar file, atau false jika gagal
     */
    public function listFiles(string $path = '.')
    {
        $handle = $this->connection->getHandle();

        // Dapatkan listing lengkap
        $listing = @ftp_nlist($handle, $path);

        if ($listing === false) {
            return false;
        }

        return $listing;
    }

    /**
     * Delete direktori (rekursif)
     *
     * @param string $path Path direktori yang akan dihapus
     * @return bool True jika berhasil delete
     */
    public function deleteDir(string $path): bool
    {
        $handle = $this->connection->getHandle();

        // Pastikan path diakhiri dengan slash untuk konsistensi
        $path = rtrim($path, '/') . '/';

        // Dapatkan daftar file dan folder
        $files = $this->listFiles($path);

        if ($files === false || empty($files)) {
            // Jika tidak ada isi, langsung hapus direktori
            return @ftp_rmdir($handle, $path);
        }

        // Hapus semua isi secara rekursif
        foreach ($files as $file) {
            $fileName = basename($file);

            // Skip . dan ..
            if ($fileName === '.' || $fileName === '..') {
                continue;
            }

            $fullPath = $path . $fileName;

            // Cek apakah ini direktori atau file
            // Kita coba delete sebagai file dulu, jika gagal berarti direktori
            if (!@ftp_delete($handle, $fullPath)) {
                // Ini adalah direktori, hapus secara rekursif
                $this->deleteDir($fullPath);
            }
        }

        // Hapus direktori itu sendiri
        return @ftp_rmdir($handle, $path);
    }

    /**
     * Change directory
     *
     * @param string $path Path direktori tujuan
     * @param bool $suppressDebug Suppress error message
     * @return bool True jika berhasil change directory
     */
    public function changeDir(string $path, bool $suppressDebug = false): bool
    {
        $handle = $this->connection->getHandle();
        $result = @ftp_chdir($handle, $path);

        if (!$result && !$suppressDebug) {
            log_message('error', "FTP: tidak bisa change directory ke {$path}");
        }

        return $result;
    }

    /**
     * Dapatkan current working directory
     *
     * @return string|false Path direktori saat ini, atau false jika gagal
     */
    public function pwd()
    {
        $handle = $this->connection->getHandle();
        return @ftp_pwd($handle);
    }

    /**
     * Dapatkan parent directory
     *
     * @return bool True jika berhasil naik ke parent directory
     */
    public function cdUp(): bool
    {
        $handle = $this->connection->getHandle();
        return @ftp_cdup($handle);
    }
}
