<?php

declare(strict_types=1);

namespace Kodhe\Framework\Auth;

/**
 * Contract for the session-based authentication guard.
 */
interface AuthInterface
{
    /**
     * Attempt to authenticate a user by identifier + password.
     *
     * @return bool True on success.
     */
    public function attempt(string $identifier, string $password, bool $remember = false): bool;

    /**
     * Log in a user object (or array) directly.
     */
    public function login(array|object $user, bool $remember = false): void;

    /**
     * Log the current user out (destroy session).
     */
    public function logout(bool $destroySession = true): void;

    /**
     * Whether a user is currently authenticated.
     */
    public function check(): bool;

    /**
     * The authenticated user record as array, or null.
     */
    public function user(): ?array;

    /**
     * A single attribute of the authenticated user, or null.
     */
    public function id(?string $key = null): mixed;
}
