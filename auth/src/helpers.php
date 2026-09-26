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

if (!function_exists('social_auth')) {
    /**
     * Unified login shortcut: the Auth guard wired to the native Socialite
     * component, so password login and social login share ONE session,
     * one set of events and one roles/permissions/ACL stack.
     *
     *   // start a social login (route /auth/socialite/{provider})
     *   return social_auth()->redirect('google');
     *
     *   // on the callback route
     *   $user = social_auth()->login('google');       // find/create/link + log in
     *   header('Location: ' . social_auth()->successUrl());
     *
     *   social_auth()->enabledProviders();            // ['google','github',...]
     *   social_auth()->guard()->attempt(...);         // classic password login
     *
     *   // or let handle() run the whole route for you (see also
     *   // auth/routes/socialite.php for a ready-made route table):
     *   return social_auth()->handle('google', 'start');      // -> provider
     *   return social_auth()->handle('google', 'callback');   // -> home/intended
     *
     * @param string|null $provider Optional provider name; returns that
     *                              driver's login URL when given.
     */
    function social_auth(?string $provider = null): mixed
    {
        static $bridge = null;

        if ($bridge === null) {
            $bridge = new \Kodhe\Framework\Auth\SocialiteAuth(
                \Kodhe\Framework\Auth\SocialiteAuth::readConfigFile()
            );
        }

        if ($provider !== null) {
            return $bridge->loginUrl($provider);
        }

        return $bridge;
    }
}
