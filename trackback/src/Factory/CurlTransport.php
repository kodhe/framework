<?php

declare(strict_types=1);

namespace Kodhe\Trackback\Factory;

use Kodhe\Trackback\Contracts\TransportInterface;
use Kodhe\Trackback\Support\TrackbackConfig;

/**
 * cURL transport implementation.
 */
class CurlTransport implements TransportInterface
{
    private int $timeout;
    private string $userAgent;

    public function __construct(?TrackbackConfig $config = null)
    {
        $config = $config ?? new TrackbackConfig();
        $this->timeout = $config->getTimeout();
        $this->userAgent = $config->getUserAgent();
    }

    /**
     * Send a trackback request using cURL.
     */
    public function send(string $url, string $data): array
    {
        $ch = curl_init();

        if ($ch === false) {
            throw new \Kodhe\Trackback\Exceptions\TransportException('Failed to initialize cURL');
        }

        // Extract tb_id from URL if present
        $tbId = $this->extractTrackbackId($url);
        if ($tbId !== false) {
            $data = 'tb_id=' . urlencode($tbId) . '&' . $data;
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'Content-type: application/x-www-form-urlencoded',
                'User-Agent: ' . $this->userAgent,
            ],
            CURLOPT_FOLLOWLOCATION => false, // Prevent redirects for security
        ]);

        $response = curl_exec($ch);
        
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Kodhe\Trackback\Exceptions\TransportException('cURL error: ' . $error);
        }

        curl_close($ch);

        return [
            'success' => stripos($response, '<error>0</error>') !== false,
            'body' => $response,
            'error' => null,
        ];
    }

    /**
     * Set connection timeout in seconds.
     */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = max(1, $seconds);
        return $this;
    }

    /**
     * Set user agent string.
     */
    public function setUserAgent(string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    /**
     * Extract trackback ID from URL.
     */
    private function extractTrackbackId(string $url): string|false
    {
        $tbId = '';

        if (strpos($url, '?') !== false) {
            $parts = explode('/', $url);
            $end = end($parts);

            if (!is_numeric($end)) {
                $end = prev($parts) ?: '';
            }

            $parts = explode('=', $end);
            $tbId = end($parts);
        } else {
            $url = rtrim($url, '/');
            $parts = explode('/', $url);
            $tbId = end($parts);

            if (!is_numeric($tbId)) {
                $tbId = prev($parts) ?: '';
            }
        }

        return ctype_digit((string) $tbId) ? $tbId : false;
    }
}
