<?php

/**
 * HTTP Component Configuration
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Response Settings
    |--------------------------------------------------------------------------
    */
    'response' => [
        'charset' => 'UTF-8',
        'content_type' => 'text/html',
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Middleware
    |--------------------------------------------------------------------------
    | These middleware will be run for every request
    */
    'global_middleware' => [
        // \Kodhe\Framework\Http\Middleware\Http\ThrottleRequests::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Groups
    |--------------------------------------------------------------------------
    */
    'middleware_groups' => [
        'web' => [
            // Session middleware, CSRF protection, etc.
        ],
        
        'api' => [
            // API throttling, CORS, etc.
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    | Individual middleware that can be assigned to routes
    */
    'route_middleware' => [
        'auth' => \Kodhe\Framework\Http\Middleware\Http\AuthMiddleware::class,
        'guest' => \Kodhe\Framework\Http\Middleware\Http\GuestMiddleware::class,
        'throttle' => \Kodhe\Framework\Http\Middleware\Http\ThrottleRequests::class,
        'api.version' => \Kodhe\Framework\Http\Middleware\Http\ApiVersionMiddleware::class,
        'subdomain' => \Kodhe\Framework\Http\Middleware\Http\SubdomainMiddleware::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limiting' => [
        'enabled' => true,
        'default' => [
            'max_attempts' => 60,
            'decay_minutes' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    */
    'security_headers' => [
        'x_frame_options' => 'SAMEORIGIN',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'referrer_policy' => 'strict-origin-when-cross-origin',
    ],
];
