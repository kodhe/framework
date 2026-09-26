<?php

declare(strict_types=1);

use Kodhe\Framework\Auth\Auth;

if (!function_exists('auth')) {
    /**
     * Session-based authentication shortcut.
     *
     *   auth()                 => Auth instance (guard)
     *   auth()->check()        => bool
     *   auth()->user()         => ?array  (user record, no password hash)
     *   auth()->id()           => int|string|null
     *   auth()->id('name')     => mixed   (single attribute)
     *
     * @param string|null $key Optional user attribute to fetch directly.
     */
    function auth(?string $key = null): mixed
    {
        static $guard = null;

        if ($guard === null) {
            $guard = new Auth();
        }

        if ($key !== null) {
            return $guard->id($key);
        }

        return $guard;
    }
}
