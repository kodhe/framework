<?php

declare(strict_types=1);

namespace Kodhe\Framework\Auth;

// Note: this file lives in src/Support/, so a PSR-4-only autoloader would
// look for it under the name Kodhe\Framework\Auth\Support\Facade and fail to
// find the documented class Kodhe\Framework\Auth\Facade. The root composer.json
// therefore includes "auth/src" in its classmap so the correct class is found;
// the class_exists() guard below keeps things safe if both aliases are ever
// autoloaded within one request.
use Kodhe\Framework\Auth\Auth;

if (!class_exists('Kodhe\\Framework\\Auth\\Facade', false)) {
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

        public static function register(array $input): int|string
        {
            return self::guard()->register($input);
        }

        public static function changePassword(string $current, string $new): bool
        {
            return self::guard()->changePassword($current, $new);
        }

        public static function sendPasswordReset(string $identifierValue): ?string
        {
            return self::guard()->sendPasswordReset($identifierValue);
        }

        public static function resetPassword(string $identifierValue, string $token, string $newPassword): bool
        {
            return self::guard()->resetPassword($identifierValue, $token, $newPassword);
        }

        public static function requestVerification(string $identifierValue): ?string
        {
            return self::guard()->requestVerification($identifierValue);
        }

        public static function verifyEmail(string $identifierValue, string $token): bool
        {
            return self::guard()->verifyEmail($identifierValue, $token);
        }

        public static function isVerified(?array $user = null): bool
        {
            return self::guard()->isVerified($user);
        }

        public static function attempts(string $identifier): int
        {
            return self::guard()->attempts($identifier);
        }

        public static function retryAfter(string $identifier): int
        {
            return self::guard()->retryAfter($identifier);
        }

        public static function tooManyAttempts(string $identifier): bool
        {
            return self::guard()->tooManyAttempts($identifier);
        }

        public static function clearFailedAttempts(string $identifier): void
        {
            self::guard()->clearFailedAttempts($identifier);
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
