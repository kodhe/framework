<?php

declare(strict_types=1);

namespace Kodhe\Zip\Compression;

use Kodhe\Zip\Contracts\CompressionStrategyInterface;

/**
 * Store compression strategy (no compression)
 */
class StoreStrategy implements CompressionStrategyInterface
{
    public function compress(string $data): array
    {
        return [
            'compressed' => $data,
            'size' => strlen($data),
            'method' => 0 // STORE
        ];
    }

    public function getLevel(): int
    {
        return 0;
    }

    public function getMethod(): int
    {
        return 0;
    }
}
