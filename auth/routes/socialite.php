<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Social login routes (Auth <-> Socialite integration)
|--------------------------------------------------------------------------
|
| These routes complete the ONE authentication system: password login goes
| through auth()->attempt(), social login goes through the endpoints below —
| both end in the same guard/session/roles stack.
|
| The file returns a framework-agnostic route table: an array of
|   ['method' => ..., 'path' => ..., 'handler' => Closure]
| so it can be mounted on ANY dispatcher without coupling to one:
|
|   // Plain PHP front controller / any micro-router:
|   foreach (require $routesFile as $route) {
|       if ($route['method'] === $_SERVER['REQUEST_METHOD']) {
|           $response = ($route['handler'])($params);   // after path matching
|           header('Location: ' . $response->getTargetUrl());
|       }
|   }
|
|   // Laravel-style:
|   foreach (...same array... as $r) {
|       Route::match(explode('|', $r['method']), $r['path'], $r['handler']);
|   }
|
| Paths honour the 'login_route' config key (default /auth/socialite), so
| overriding it in config/socialite_auth.php moves all endpoints at once.
|
*/

use Kodhe\Framework\Auth\SocialiteAuth;

$bridge = static function (): SocialiteAuth {
    if (function_exists('social_auth')) {
        return social_auth();
    }

    return new SocialiteAuth(SocialiteAuth::readConfigFile());
};

$base = static function (): string {
    $cfg  = SocialiteAuth::readConfigFile();
    $path = $cfg['login_route'] ?? '/auth/socialite';

    return '/' . trim((string) $path, '/');
};

return [
    [
        'method'  => 'GET',
        'path'    => $base() . '/{provider}',
        'handler' => static function (array $params = []) use ($bridge) {
            return $bridge()->handle(
                (string) ($params['provider'] ?? ''),
                'start',
                ['intended' => $params['intended'] ?? null]
            );
        },
    ],
    [
        'method'  => 'GET',
        'path'    => $base() . '/{provider}/connect',
        'handler' => static function (array $params = []) use ($bridge) {
            // "Link this social account to my current profile" entry point.
            return $bridge()->handle(
                (string) ($params['provider'] ?? ''),
                'start',
                ['connect' => true]
            );
        },
    ],
    [
        'method'  => 'GET',
        'path'    => $base() . '/{provider}/callback',
        'handler' => static function (array $params = []) use ($bridge) {
            return $bridge()->handle(
                (string) ($params['provider'] ?? ''),
                'callback'
            );
        },
    ],
];
