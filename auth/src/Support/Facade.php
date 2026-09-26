<?php

declare(strict_types=1);

namespace Kodhe\Framework\Auth;

use Kodhe\Framework\Auth\Auth;

if (!class_exists('Kodhe\\Framework\\Auth\\Facade')) {
    /**
     * Static facade for the auth guard.
     *
     *   use Kodhe\Framework\Auth\Facade as Auth;
     *
     *   Auth::attempt($email, $password, $remember);
     *   Auth::check();  Auth::user();  Auth::id();  Auth::logout();
     *
     * Functionally identical to the global auth() helper; both share
     * the same singleton guard instance.
     */
    class Facade
    {
        protected static ?Auth $guard = null;

        public static function guard(): Auth
        {
            return self::$guard ??= new Auth();
        }

        /**
         * Swap the underlying guard (mainly for tests / custom configs).
         */
        public static function setGuard(?Auth $guard): void
        {
            self::$guard = $guard;
        }

        public static function attempt(string $identifier, string $password, bool $remember = false, array $extra = []): bool
        {
            return self::guard()->attempt($identifier, $password, $remember, $extra);
        }

        public static function login(array|object $user, bool $remember = false): void
        {
            self::guard()->login($user, $remember);
        }

        public static function setUser(array|object|null $user): void
        {
            self::guard()->setUser($user);
        }

        public static function logout(bool $destroySession = true): void
        {
            self::guard()->logout($destroySession);
        }

        public static function check(): bool
        {
            return self::guard()->check();
        }

        public static function user(): ?array
        {
            return self::guard()->user();
        }

        public static function id(?string $key = null): mixed
        {
            return self::guard()->id($key);
        }

        public static function hashPassword(string $password): string
        {
            return Auth::hashPassword($password);
        }

        /**
         * Pass-through to the configured UserProvider (registration etc.).
         */
        public static function __callStatic(string $method, array $arguments): mixed
        {
            return self::guard()->{$method}(...$arguments);
        }
    }
}
