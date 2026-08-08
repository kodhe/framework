<?php

declare(strict_types=1);

namespace Kodhe\Framework\Zip\Compression;

use Kodhe\Framework\Zip\Contracts\CompressionStrategyInterface;

/**
 * Deflate compression strategy (default)
 */
class DeflateStrategy implements CompressionStrategyInterface
{
    private int $level;

    public function __construct(int $level = 2)
    {
        $this->level = max(0, min(9, $level));
    }

    public function compress(string $data): array
    {
        if ($this->level === 0) {
            // Store without compression
            return [
                'compressed' => $data,
                'size' => strlen($data),
                'method' => 0
            ];
        }

        $gzdata = gzcompress($data, $this->level);
        // Remove gzip header (2 bytes) and trailer (4 bytes)
        $compressed = substr($gzdata, 2, -4);

        return [
            'compressed' => $compressed,
            'size' => strlen($compressed),
            'method' => 8 // DEFLATE
        ];
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getMethod(): int
    {
        return $this->level === 0 ? 0 : 8;
    }
}
