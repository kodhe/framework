<?php

declare(strict_types=1);

namespace Kodhe\Framework\Auth\Contracts;

/**
 * Optional contract for providers that can update arbitrary user columns.
 *
 * Auth uses it for: password changes/resets, email-verification flips and
 * storing password-reset / verification hashes + expiry timestamps.
 */
interface UpdatableProviderInterface
{
    /**
     * Persist one or more columns for the given user id.
     *
     * @param array $columns Column => value pairs (e.g. ['password' => '...']).
     */
    public function updateUser(int|string $id, array $columns): void;
}
