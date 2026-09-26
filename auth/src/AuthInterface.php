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
     * @param array $extra Additional columns the provider must match when
     *                     looking the user up (e.g. ['is_active' => 1]).
     *
     * @return bool True on success.
     */
    public function attempt(string $identifier, string $password, bool $remember = false, array $extra = []): bool;

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

    /**
     * Register a new user (provider must implement RegisterableProviderInterface).
     *
     * @return int|string The new user's primary key.
     * @throws AuthException On invalid input / duplicate account / unsupported provider.
     */
    public function register(array $input): int|string;

    /**
     * Change the current user's password after verifying the old one.
     */
    public function changePassword(string $current, string $new): bool;

    /**
     * Generate + persist a password-reset token; returns the RAW token for
     * the e-mail link, or null when the account does not exist.
     */
    public function sendPasswordReset(string $identifierValue): ?string;

    /**
     * Complete a password reset using the raw token from the e-mail link.
     */
    public function resetPassword(string $identifierValue, string $token, string $newPassword): bool;

    /**
     * Generate + persist an email-verification token; returns the RAW token
     * for the e-mail link, or null when the account does not exist.
     */
    public function requestVerification(string $identifierValue): ?string;

    /**
     * Mark an e-mail as verified using the token from the verification link.
     */
    public function verifyEmail(string $identifierValue, string $token): bool;

    /**
     * Whether the current (or given) user record has a verified e-mail.
     */
    public function isVerified(?array $user = null): bool;

    /**
     * Failed login attempts recorded for an identifier within the window.
     */
    public function attempts(string $identifier): int;

    /**
     * Seconds until the identifier may attempt login again (0 = free).
     */
    public function retryAfter(string $identifier): int;

    /**
     * Whether the identifier is currently locked out by throttling.
     */
    public function tooManyAttempts(string $identifier): bool;

    /**
     * Record one failed login attempt for the identifier.
     */
    public function addFailedAttempt(string $identifier): void;

    /**
     * Reset the failed-attempt counter for the identifier.
     */
    public function clearFailedAttempts(string $identifier): void;

    // ---- Authorization (roles / permissions / hierarchy / ACL) ----

    /**
     * Role names held by the current (or given) user record.
     *
     * @return string[]
     */
    public function roles(?array $user = null): array;

    /**
     * Primary role name, or null.
     */
    public function role(?array $user = null): ?string;

    /**
     * All effective permission names (direct grants + roles expanded via the
     * hierarchy).
     *
     * @return string[]
     */
    public function permissions(?array $user = null): array;

    /**
     * Plain permission check against grants (no ACL consulted).
     */
    public function hasPermission(string $permission, ?array $user = null): bool;

    /**
     * Multi-rule check: every listed permission must pass.
     */
    public function hasAllPermissions(string|array $permissions, ?array $user = null, array $context = []): bool;

    /**
     * Multi-rule check: at least one listed permission must pass.
     */
    public function hasAnyPermission(string|array $permissions, ?array $user = null, array $context = []): bool;

    public function hasRole(string $role, ?array $user = null): bool;

    public function anyRole(string|array $roles, ?array $user = null): bool;

    public function allRoles(string|array $roles, ?array $user = null): bool;

    /**
     * Full multi-rule authorization (grants + hierarchy + ACL rules).
     */
    public function allows(string $permission, ?array $user = null, array $context = []): bool;

    public function can(string $permission, array $context = []): bool;

    public function cannot(string $permission, array $context = []): bool;

    /**
     * Throwing variant of allows(): raises AuthException on denial.
     */
    public function authorize(string $permission, ?string $message = null, array $context = []): void;

    /**
     * Hierarchy guard: may the current user act upon the target user?
     */
    public function canActOn(array|object|int|string $target, ?array $actor = null): bool;
}
