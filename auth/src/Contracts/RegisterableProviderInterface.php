<?php

declare(strict_types=1);

namespace Kodhe\Framework\Auth\Contracts;

/**
 * Optional contract for providers that support new-user registration.
 *
 * The Auth::register() helper only requires the two methods below; a
 * provider may implement more (e.g. its own create()) without conflict.
 */
interface RegisterableProviderInterface
{
    /**
     * Whether an account already exists for the given identifier value
     * (e.g. a taken e-mail address). Used by Auth::register() to fail fast.
     */
    public function hasIdentifier(string $identifier, string $value): bool;

    /**
     * Persist a brand-new user.
     *
     * @param array $attributes Safe attributes (name, email, ...). The
     *                          password column is NOT part of this array.
     * @param string $passwordHash Already-hashed password (see Auth::hashPassword()).
     * @return int|string The new user's primary key.
     */
    public function createUser(array $attributes, string $passwordHash): int|string;
}
