<?php

declare(strict_types=1);

namespace Kodhe\Zip\Factory;

use Kodhe\Zip\Contracts\CompressionStrategyInterface;
use Kodhe\Zip\Compression\DeflateStrategy;
use Kodhe\Zip\Compression\StoreStrategy;

/**
 * Factory for creating compression strategies
 */
class CompressionFactory
{
    /**
     * Create a compression strategy based on level
     */
    public static function create(int $level = 2): CompressionStrategyInterface
    {
        if ($level === 0) {
            return new StoreStrategy();
        }

        return new DeflateStrategy($level);
    }

    /**
     * Create a deflate strategy with specific level
     */
    public static function deflate(int $level = 2): DeflateStrategy
    {
        return new DeflateStrategy($level);
    }

    /**
     * Create a store strategy (no compression)
     */
    public static function store(): StoreStrategy
    {
        return new StoreStrategy();
    }
}
