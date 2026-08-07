<?php

/**
 * Routing Configuration File
 * 
 * Configure routing behavior, patterns, and options.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Routing Mode
    |--------------------------------------------------------------------------
    |
    | Enable modern routing, legacy routing, or both.
    | When both are enabled, modern routing is tried first.
    |
    */
    'enable_modern_routing' => true,
    'enable_legacy_routing' => false,
    'prefer_modern' => false,

    /*
    |--------------------------------------------------------------------------
    | Route Caching
    |--------------------------------------------------------------------------
    |
    | Enable route caching for production environments.
    | Disable during development for automatic route reloading.
    |
    */
    'cache_routes' => ENVIRONMENT === 'production',

    /*
    |--------------------------------------------------------------------------
    | Namespace Detection
    |--------------------------------------------------------------------------
    |
    | Automatically detect controller namespaces from route actions.
    |
    */
    'auto_detect_namespace' => true,
    'allow_namespace_in_routes' => true,

    /*
    |--------------------------------------------------------------------------
    | Controller Configuration
    |--------------------------------------------------------------------------
    |
    | Controller suffix and default 404 handler.
    |
    */
    'controller_suffix' => '',
    'default_404_controller' => 'FileNotFound',
    'default_404_namespace' => 'Kodhe\\Controllers\\Error\\',

    /*
    |--------------------------------------------------------------------------
    | Default Parameter Patterns
    |--------------------------------------------------------------------------
    |
    | Define default regex patterns for route parameters.
    |
    */
    'patterns' => [
        'id' => '([0-9]+)',
        'slug' => '([a-z0-9-]+)',
        'uuid' => '([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})',
        'any' => '(.+)',
        'string' => '([a-zA-Z]+)',
        'alpha' => '([a-zA-Z]+)',
        'num' => '([0-9]+)',
        'alnum' => '([a-zA-Z0-9]+)',
        'subdomain' => '([a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)',
        'domain' => '([a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*)',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Versioning
    |--------------------------------------------------------------------------
    |
    | Default API version and version configuration.
    |
    */
    'api' => [
        'default_version' => '1',
        'versions' => [
            '1' => [
                'prefix' => 'api/v1',
                'middleware' => ['api'],
                'deprecated' => false,
            ],
            '2' => [
                'prefix' => 'api/v2',
                'middleware' => ['api'],
                'deprecated' => false,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Default rate limiting configuration.
    |
    */
    'rate_limiting' => [
        'enabled' => true,
        'default_max_attempts' => 60,
        'default_decay_minutes' => 1,
    ],
];
