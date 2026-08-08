<?php

declare(strict_types=0);

namespace Kodhe\Framework\Xmlrpc\Transport;

use Kodhe\Framework\Xmlrpc\Contracts\TransportInterface;
use Kodhe\Framework\Xmlrpc\Exceptions\TransportException;

/**
 * Socket-based transport implementation for XML-RPC
 */
class SocketTransport implements TransportInterface
{
    /**
     * @var resource|null
     */
    private $connection = null;

    /**
     * @var string
     */
    private $lastHost = '';

    /**
     * @var int
     */
    private $lastPort = 0;

    /**
     * Send XML-RPC request and return response
     *
     * @param string $payload
     * @param string $url
     * @param int $port
     * @param int $timeout
     * @return array
     * @throws TransportException
     */
    public function send(string $payload, string $url, int $port = 80, int $timeout = 5): array
    {
        $parts = parse_url($url);
        $host = $parts['host'] ?? $url;
        $path = $parts['path'] ?? '/';

        if (isset($parts['user'], $parts['pass'])) {
            $authHeader = 'Authorization: Basic '.base64_encode($parts['user'].':'.$parts['pass'])."\r\n";
        } else {
            $authHeader = '';
        }

        // Reuse connection if possible
        if ($this->connection && ($this->lastHost !== $host || $this->lastPort !== $port)) {
            $this->close();
        }

        if (!$this->connection) {
            $errno = 0;
            $errstr = '';
            $this->connection = @fsockopen($host, $port, $errno, $errstr, $timeout);

            if (!is_resource($this->connection)) {
                throw new TransportException("Could not connect to {$host}:{$port}", $errno, null, $url);
            }

            $this->lastHost = $host;
            $this->lastPort = $port;
        }

        $request = "POST {$path} HTTP/1.0\r\n"
            ."Host: {$host}\r\n"
            .'Content-Type: text/xml'."\r\n"
            .$authHeader
            .'User-Agent: XML-RPC for CodeIgniter'."\r\n"
            .'Content-Length: '.strlen($payload)."\r\n\r\n"
            .$payload;

        stream_set_timeout($this->connection, $timeout);

        // Write request
        $written = 0;
        $length = strlen($request);
        $timestamp = 0;

        while ($written < $length) {
            $result = fwrite($this->connection, substr($request, $written));

            if ($result === false) {
                $this->close();
                throw new TransportException('Failed to write request', 0, null, $url);
            } elseif ($result === 0) {
                if ($timestamp === 0) {
                    $timestamp = time();
                } elseif ($timestamp < (time() - $timeout)) {
                    $this->close();
                    throw new TransportException('Request timeout', 0, null, $url);
                }
            } else {
                $timestamp = 0;
                $written += $result;
            }
        }

        // Read response
        $response = '';
        while (!feof($this->connection)) {
            $datum = fread($this->connection, 4096);
            if ($datum === false || $datum === '') {
                break;
            }
            $response .= $datum;
        }

        return [
            'raw' => $response,
            'headers' => $this->parseHeaders($response),
            'body' => $this->parseBody($response),
        ];
    }

    /**
     * Close the connection
     *
     * @return void
     */
    public function close(): void
    {
        if (is_resource($this->connection)) {
            fclose($this->connection);
            $this->connection = null;
        }
        $this->lastHost = '';
        $this->lastPort = 0;
    }

    /**
     * Parse HTTP headers from response
     *
     * @param string $response
     * @return array
     */
    private function parseHeaders(string $response): array
    {
        $headers = [];
        $lines = explode("\r\n", $response);

        foreach ($lines as $line) {
            if (strlen($line) < 1) {
                break;
            }
            $headers[] = $line;
        }

        return $headers;
    }

    /**
     * Parse HTTP body from response
     *
     * @param string $response
     * @return string
     */
    private function parseBody(string $response): string
    {
        $lines = explode("\r\n", $response);

        // Skip headers
        while (($line = array_shift($lines))) {
            if (strlen($line) < 1) {
                break;
            }
        }

        return implode("\r\n", $lines);
    }

    /**
     * Destructor - cleanup
     */
    public function __destruct()
    {
        $this->close();
    }
}
