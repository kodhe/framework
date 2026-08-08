<?php

declare(strict_types=1);

namespace Kodhe\Framework\Trackback\Support;

/**
 * Configuration for Trackback operations.
 */
class TrackbackConfig
{
    /**
     * Character encoding.
     */
    public const DEFAULT_CHARSET = 'UTF-8';

    /**
     * Maximum excerpt length.
     */
    public const DEFAULT_EXCERPT_LENGTH = 500;

    /**
     * Connection timeout in seconds.
     */
    public const DEFAULT_TIMEOUT = 10;

    /**
     * Maximum payload size in bytes.
     */
    public const MAX_PAYLOAD_SIZE = 65536; // 64KB

    /**
     * Maximum URL length.
     */
    public const MAX_URL_LENGTH = 2048;

    /**
     * Allowed protocols for URLs.
     */
    public const ALLOWED_PROTOCOLS = ['http', 'https'];

    /**
     * User agent string.
     */
    public const USER_AGENT = 'CodeIgniter Trackback';

    private string $charset;
    private int $excerptLength;
    private int $timeout;
    private int $maxPayloadSize;
    private int $maxUrlLength;
    private array $allowedProtocols;
    private string $userAgent;
    private bool $convertAscii;

    public function __construct()
    {
        $this->charset = self::DEFAULT_CHARSET;
        $this->excerptLength = self::DEFAULT_EXCERPT_LENGTH;
        $this->timeout = self::DEFAULT_TIMEOUT;
        $this->maxPayloadSize = self::MAX_PAYLOAD_SIZE;
        $this->maxUrlLength = self::MAX_URL_LENGTH;
        $this->allowedProtocols = self::ALLOWED_PROTOCOLS;
        $this->userAgent = self::USER_AGENT;
        $this->convertAscii = true;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function setCharset(string $charset): self
    {
        $this->charset = $charset;
        return $this;
    }

    public function getExcerptLength(): int
    {
        return $this->excerptLength;
    }

    public function setExcerptLength(int $length): self
    {
        $this->excerptLength = max(1, $length);
        return $this;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function setTimeout(int $seconds): self
    {
        $this->timeout = max(1, $seconds);
        return $this;
    }

    public function getMaxPayloadSize(): int
    {
        return $this->maxPayloadSize;
    }

    public function setMaxPayloadSize(int $bytes): self
    {
        $this->maxPayloadSize = max(1024, $bytes);
        return $this;
    }

    public function getMaxUrlLength(): int
    {
        return $this->maxUrlLength;
    }

    public function setMaxUrlLength(int $length): self
    {
        $this->maxUrlLength = max(100, $length);
        return $this;
    }

    public function getAllowedProtocols(): array
    {
        return $this->allowedProtocols;
    }

    public function setAllowedProtocols(array $protocols): self
    {
        $this->allowedProtocols = $protocols;
        return $this;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function setUserAgent(string $agent): self
    {
        $this->userAgent = $agent;
        return $this;
    }

    public function isConvertAscii(): bool
    {
        return $this->convertAscii;
    }

    public function setConvertAscii(bool $convert): self
    {
        $this->convertAscii = $convert;
        return $this;
    }
}
