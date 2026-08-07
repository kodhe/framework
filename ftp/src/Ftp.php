<?php

declare(strict_types=1);

namespace Kodhe\Ftp;

use Kodhe\Ftp\Contracts\FtpInterface;
use Kodhe\Ftp\Contracts\ConnectionInterface;
use Kodhe\Ftp\Connection\FtpConnection;
use Kodhe\Ftp\Connection\FtpSslConnection;
use Kodhe\Ftp\Operations\FileOperations;
use Kodhe\Ftp\Operations\DirectoryOperations;

/**
 * Class Ftp
 *
 * FTP Library untuk CodeIgniter 3 dengan arsitektur modular PSR-4
 * Facade pattern yang mendelegasikan operasi ke FileOperations dan DirectoryOperations
 *
 * @package     Kodhe\Ftp
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class Ftp implements FtpInterface
{
    /**
     * @var ConnectionInterface|null Koneksi FTP
     */
    protected ?ConnectionInterface $connection = null;

    /**
     * @var FileOperations|null Operasi file
     */
    protected ?FileOperations $fileOps = null;

    /**
     * @var DirectoryOperations|null Operasi direktori
     */
    protected ?DirectoryOperations $dirOps = null;

    /**
     * @var bool Mode debug
     */
    protected bool $debug = true;

    /**
     * @var array Konfigurasi FTP
     */
    protected array $config = [];

    /**
     * Constructor
     *
     * @param array $config Konfigurasi (opsional, bisa dipanggil via connect())
     */
    public function __construct(array $config = [])
    {
        if (!empty($config)) {
            $this->connect($config);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function connect(array $config = []): bool
    {
        // Merge config dengan default
        $this->config = array_merge($this->config, $config);

        // Pilih jenis koneksi berdasarkan SSL
        $this->connection = !empty($this->config['ssl'])
            ? new FtpSslConnection($this->config)
            : new FtpConnection($this->config);

        // Buka koneksi
        $connected = $this->connection->open();

        if ($connected) {
            // Inisialisasi operations setelah berhasil connect
            $this->fileOps = new FileOperations($this->connection);
            $this->dirOps = new DirectoryOperations($this->connection);

            // Set passive mode jika dikonfigurasi
            if (!empty($this->config['passive'])) {
                ftp_pasv($this->connection->getHandle(), true);
            }
        } elseif ($this->debug) {
            log_message('error', 'FTP: gagal terhubung ke ' . ($this->config['hostname'] ?? 'unknown'));
        }

        return $connected;
    }

    /**
     * {@inheritdoc}
     */
    public function upload(string $locpath, string $rempath, string $mode = 'auto', ?int $permissions = null): bool
    {
        if (!$this->ensureConnected()) {
            return false;
        }

        return $this->fileOps->upload($locpath, $rempath, $mode, $permissions);
    }

    /**
     * {@inheritdoc}
     */
    public function download(string $rempath, string $locpath, string $mode = 'auto'): bool
    {
        if (!$this->ensureConnected()) {
            return false;
        }

        return $this->fileOps->download($rempath, $locpath, $mode);
    }

    /**
     * {@inheritdoc}
     */
    public function rename(string $old_file, string $new_file, bool $move = false): bool
    {
        if (!$this->ensureConnected()) {
            return false;
        }

        return $this->fileOps->rename($old_file, $new_file, $move);
    }

    /**
     * {@inheritdoc}
     */
    public function move(string $old_file, string $new_file): bool
    {
        return $this->rename($old_file, $new_file, true);
    }

    /**
     * {@inheritdoc}
     */
    public function delete_file(string $filepath): bool
    {
        if (!$this->ensureConnected()) {
            return false;
        }

        return $this->fileOps->delete($filepath);
    }

    /**
     * {@inheritdoc}
     */
    public function delete_dir(string $filepath): bool
    {
        if (!$this->ensureConnected()) {
            return false;
        }

        return $this->dirOps->deleteDir($filepath);
    }

    /**
     * {@inheritdoc}
     */
    public function mkdir(string $path, ?int $permissions = null)
    {
        if (!$this->ensureConnected()) {
            return false;
        }

        return $this->dirOps->mkdir($path, $permissions);
    }

    /**
     * {@inheritdoc}
     */
    public function list_files(string $path = '.')
    {
        if (!$this->ensureConnected()) {
            return false;
        }

        return $this->dirOps->listFiles($path);
    }

    /**
     * {@inheritdoc}
     */
    public function changedir(string $path, bool $suppress_debug = false): bool
    {
        if (!$this->ensureConnected()) {
            return false;
        }

        return $this->dirOps->changeDir($path, $suppress_debug);
    }

    /**
     * {@inheritdoc}
     */
    public function chmod(string $path, int $perm): bool
    {
        if (!$this->ensureConnected()) {
            return false;
        }

        return $this->fileOps->chmod($path, $perm);
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool
    {
        if ($this->connection !== null) {
            return $this->connection->close();
        }

        return true;
    }

    /**
     * Pastikan koneksi masih aktif
     *
     * @return bool True jika terhubung
     */
    protected function ensureConnected(): bool
    {
        if ($this->connection === null) {
            if ($this->debug) {
                log_message('error', 'FTP: tidak ada koneksi aktif');
            }
            return false;
        }

        if (!$this->connection->isConnected()) {
            if ($this->debug) {
                log_message('error', 'FTP: koneksi terputus');
            }
            return false;
        }

        return true;
    }

    /**
     * Set mode debug
     *
     * @param bool $debug True untuk enable debug
     * @return self
     */
    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;
        return $this;
    }

    /**
     * Dapatkan konfigurasi saat ini
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Dapatkan connection instance (untuk advanced usage)
     *
     * @return ConnectionInterface|null
     */
    public function getConnection(): ?ConnectionInterface
    {
        return $this->connection;
    }
}
