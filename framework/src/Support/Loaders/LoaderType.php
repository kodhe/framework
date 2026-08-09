<?php

declare(strict_types=0);

namespace Kodhe\Framework\Support\Loaders;

/**
 * LoaderType - Enum for loader strategy types
 * 
 * @package Kodhe\Framework\Support\Loaders
 * @since 2.0.0
 */
enum LoaderType: string
{
    case HELPER = 'helper';
    case LIBRARY = 'library';
    case MODEL = 'model';
    case VIEW = 'view';
    case CONFIG = 'config';
    case LANGUAGE = 'language';
    
    /**
     * Get file suffix for loader type
     */
    public function getSuffix(): string
    {
        return match($this) {
            self::HELPER => '_helper.php',
            self::LIBRARY => '.php',
            self::MODEL => '.php',
            self::VIEW => '.php',
            self::CONFIG => '.php',
            self::LANGUAGE => '.php',
        };
    }
    
    /**
     * Get directory name for loader type
     */
    public function getDirectory(): string
    {
        return match($this) {
            self::HELPER => 'helpers',
            self::LIBRARY => 'libraries',
            self::MODEL => 'models',
            self::VIEW => 'views',
            self::CONFIG => 'config',
            self::LANGUAGE => 'language',
        };
    }
    
    /**
     * Check if loader type supports auto-discovery
     */
    public function supportsDiscovery(): bool
    {
        return in_array($this, [
            self::HELPER,
            self::LIBRARY,
            self::MODEL,
        ], true);
    }
}
