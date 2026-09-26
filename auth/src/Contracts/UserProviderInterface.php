<?php

declare(strict_types=1);

namespace Kodhe\Framework\Auth\Contracts;

/**
 * Bridge between the Auth guard and your storage layer (model, DB, API...).
 *
 * Implement this in your application so the framework stays agnostic
 * about where users live.
 */
interface UserProviderInterface
{
    /**
     * Fetch a user record by its unique identifier column
     * (e.g. "email" or "username"). Return null when not found.
     *
     * @param array $extra Additional columns that must match (e.g. ['is_active' => 1]).
     */
    public function retrieveByIdentifier(string $identifier, string $value, array $extra = []): ?array;

    /**
     * Fetch a user record by primary key. Return null when not found.
     */
    public function retrieveById(int|string $id): ?array;

    /**
     * Persist updated attributes for the given user (used by "remember me").
     */
    public function updateRememberToken(int|string $id, ?string $token): void;
}
