<?php

namespace Kodhe\Ftp\Connection;

use Kodhe\Ftp\Contracts\ConnectionInterface;

/**
 * Class FtpSslConnection
 *
 * Wrapper untuk koneksi FTP over SSL (FTPS)
 *
 * @package     Kodhe\Ftp\Connection
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class FtpSslConnection implements ConnectionInterface
{
    /**
     * @var resource|null Handle koneksi FTP SSL
     */
    protected $handle;

    /**
     * @var array Konfigurasi koneksi
     */
    protected array $config;

    /**
     * Constructor
     *
     * @param array $config Konfigurasi koneksi
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function open(): bool
    {
        $hostname = $this->config['hostname'] ?? '';
        $port = $this->config['port'] ?? 21;
        $timeout = $this->config['timeout'] ?? 90;

        // Coba connect ke server dengan SSL
        $this->handle = @ftp_ssl_connect($hostname, $port, $timeout);

        if ($this->handle === false) {
            return false;
        }

        // Set opsi passive mode jika dikonfigurasi
        if (!empty($this->config['passive'])) {
            ftp_pasv($this->handle, true);
        }

        // Login dengan username dan password
        $username = $this->config['username'] ?? '';
        $password = $this->config['password'] ?? '';

        return @ftp_login($this->handle, $username, $password);
    }

    /**
     * {@inheritdoc}
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool
    {
        if ($this->handle !== null) {
            return ftp_close($this->handle);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected(): bool
    {
        return $this->handle !== null && @ftp_pwd($this->handle) !== false;
    }
}
