<?php

declare(strict_types=0);

namespace Kodhe\Framework\Zip\Contracts;

/**
 * Interface for compression strategies
 */
interface CompressionStrategyInterface
{
    /**
     * Compress data
     *
     * @param string $data Data to compress
     * @return array{compressed: string, size: int, method: int}
     */
    public function compress(string $data): array;

    /**
     * Get compression level (0-9)
     */
    public function getLevel(): int;

    /**
     * Get compression method identifier
     */
    public function getMethod(): int;
}
