<?php

namespace Kodhe\Framework\Ftp\Operations;

use Kodhe\Framework\Ftp\Contracts\ConnectionInterface;
use Kodhe\Framework\Ftp\Validation\ModeResolver;

/**
 * Class FileOperations
 *
 * Menangani operasi file FTP (upload, download, delete, rename, chmod)
 *
 * @package     Kodhe\Ftp\Operations
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class FileOperations
{
    /**
     * @var ConnectionInterface Koneksi FTP
     */
    private ConnectionInterface $connection;

    /**
     * @var ModeResolver Resolver untuk mode transfer
     */
    private ModeResolver $modeResolver;

    /**
     * Constructor
     *
     * @param ConnectionInterface $connection Koneksi FTP
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->modeResolver = new ModeResolver();
    }

    /**
     * Upload file ke server FTP
     *
     * @param string $locpath Path file lokal
     * @param string $rempath Path file remote
     * @param string $mode Mode transfer (auto, ascii, binary)
     * @param int|null $permissions Permissions chmod (opsional)
     * @return bool True jika berhasil upload
     */
    public function upload(string $locpath, string $rempath, string $mode = 'auto', ?int $permissions = null): bool
    {
        // Cek apakah file lokal ada
        if (!file_exists($locpath)) {
            log_message('error', "FTP: file lokal tidak ditemukan: {$locpath}");
            return false;
        }

        // Cek apakah file lokal bisa dibaca
        if (!is_readable($locpath)) {
            log_message('error', "FTP: file lokal tidak bisa dibaca: {$locpath}");
            return false;
        }

        // Resolve mode transfer
        if ($mode === 'auto') {
            $mode = $this->modeResolver->resolve($locpath);
        }

        // Konversi mode ke konstanta FTP
        $ftpMode = $mode === 'ascii' ? FTP_ASCII : FTP_BINARY;

        // Upload file
        $handle = $this->connection->getHandle();
        $result = @ftp_put($handle, $rempath, $locpath, $ftpMode);

        // Set permissions jika diminta
        if ($result && $permissions !== null) {
            $this->chmod($rempath, $permissions);
        }

        return (bool) $result;
    }

    /**
     * Download file dari server FTP
     *
     * @param string $rempath Path file remote
     * @param string $locpath Path file lokal
     * @param string $mode Mode transfer (auto, ascii, binary)
     * @return bool True jika berhasil download
     */
    public function download(string $rempath, string $locpath, string $mode = 'auto'): bool
    {
        // Resolve mode transfer
        if ($mode === 'auto') {
            $mode = $this->modeResolver->resolve($rempath);
        }

        // Konversi mode ke konstanta FTP
        $ftpMode = $mode === 'ascii' ? FTP_ASCII : FTP_BINARY;

        // Download file
        $handle = $this->connection->getHandle();
        $result = @ftp_get($handle, $locpath, $rempath, $ftpMode);

        return (bool) $result;
    }

    /**
     * Rename file di server FTP
     *
     * @param string $old Path file lama
     * @param string $new Path file baru
     * @param bool $move Apakah ini operasi move (untuk logging)
     * @return bool True jika berhasil rename
     */
    public function rename(string $old, string $new, bool $move = false): bool
    {
        $handle = $this->connection->getHandle();
        $result = @ftp_rename($handle, $old, $new);

        return (bool) $result;
    }

    /**
     * Delete file dari server FTP
     *
     * @param string $path Path file yang akan dihapus
     * @return bool True jika berhasil delete
     */
    public function delete(string $path): bool
    {
        $handle = $this->connection->getHandle();
        $result = @ftp_delete($handle, $path);

        return (bool) $result;
    }

    /**
     * Change permissions file/direktori
     *
     * @param string $path Path file/direktori
     * @param int $perm Permissions baru (octal)
     * @return bool True jika berhasil chmod
     */
    public function chmod(string $path, int $perm): bool
    {
        $handle = $this->connection->getHandle();
        $result = @ftp_chmod($handle, $perm, $path);

        return (bool) $result;
    }

    /**
     * Dapatkan resolver mode
     *
     * @return ModeResolver
     */
    public function getModeResolver(): ModeResolver
    {
        return $this->modeResolver;
    }
}
