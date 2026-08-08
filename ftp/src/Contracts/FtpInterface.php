<?php

namespace Kodhe\Framework\Ftp\Contracts;

/**
 * Interface FtpInterface
 *
 * Contract utama untuk library FTP
 *
 * @package     Kodhe\Ftp\Contracts
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
interface FtpInterface
{
    /**
     * Connect ke server FTP
     *
     * @param array $config Konfigurasi koneksi
     * @return bool True jika berhasil connect
     */
    public function connect(array $config = []): bool;

    /**
     * Upload file ke server
     *
     * @param string $locpath Path file lokal
     * @param string $rempath Path file remote
     * @param string $mode Mode transfer (auto, ascii, binary)
     * @param int|null $permissions Permissions chmod (opsional)
     * @return bool True jika berhasil upload
     */
    public function upload(string $locpath, string $rempath, string $mode = 'auto', ?int $permissions = null): bool;

    /**
     * Download file dari server
     *
     * @param string $rempath Path file remote
     * @param string $locpath Path file lokal
     * @param string $mode Mode transfer (auto, ascii, binary)
     * @return bool True jika berhasil download
     */
    public function download(string $rempath, string $locpath, string $mode = 'auto'): bool;

    /**
     * Rename/move file
     *
     * @param string $old_file Path file lama
     * @param string $new_file Path file baru
     * @param bool $move Apakah ini operasi move
     * @return bool True jika berhasil rename
     */
    public function rename(string $old_file, string $new_file, bool $move = false): bool;

    /**
     * Move file (alias untuk rename dengan move=true)
     *
     * @param string $old_file Path file lama
     * @param string $new_file Path file baru
     * @return bool True jika berhasil move
     */
    public function move(string $old_file, string $new_file): bool;

    /**
     * Delete file
     *
     * @param string $filepath Path file yang akan dihapus
     * @return bool True jika berhasil delete
     */
    public function delete_file(string $filepath): bool;

    /**
     * Delete direktori (rekursif)
     *
     * @param string $filepath Path direktori yang akan dihapus
     * @return bool True jika berhasil delete
     */
    public function delete_dir(string $filepath): bool;

    /**
     * Buat direktori baru
     *
     * @param string $path Path direktori yang akan dibuat
     * @param int|null $permissions Permissions chmod (opsional)
     * @return string|false Path direktori yang dibuat, atau false jika gagal
     */
    public function mkdir(string $path, ?int $permissions = null);

    /**
     * List file dalam direktori
     *
     * @param string $path Path direktori
     * @return array|false Array daftar file, atau false jika gagal
     */
    public function list_files(string $path = '.');

    /**
     * Change directory
     *
     * @param string $path Path direktori tujuan
     * @param bool $suppress_debug Suppress error message
     * @return bool True jika berhasil change directory
     */
    public function changedir(string $path, bool $suppress_debug = false): bool;

    /**
     * Change permissions file/direktori
     *
     * @param string $path Path file/direktori
     * @param int $perm Permissions baru (octal)
     * @return bool True jika berhasil chmod
     */
    public function chmod(string $path, int $perm): bool;

    /**
     * Tutup koneksi
     *
     * @return bool True jika berhasil ditutup
     */
    public function close(): bool;
}
