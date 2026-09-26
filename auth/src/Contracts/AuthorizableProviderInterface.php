<?php

declare(strict_types=1);

namespace Kodhe\Framework\Auth\Contracts;

/**
 * Optional contract: storage bridge for roles, permissions and ACL rules.
 *
 * Implement this in your application's provider when you want the guard to
 * resolve authorization data (Auth::can(), Auth::role(), ACL checks). The
 * guard stays storage-agnostic: it only ever asks for role names of a user,
 * permission names of a role, and the raw rule rows backing the ACL.
 *
 * When the configured provider does NOT implement this contract, every
 * authorization method on the guard throws AuthException (a defined
 * behaviour, not a silent failure) — except the pure-config helpers
 * (Auth::hasRole() / Auth::anyRole() / Auth::allRoles()), which work off
 * the columns already present in the user record.
 */
interface AuthorizableProviderInterface
{
    /**
     * Role NAMES assigned to a user, ordered by priority (first = primary).
     *
     * @return string[]
     */
    public function rolesForUser(int|string $userId): array;

    /**
     * Permission NAMES granted to a role.
     *
     * @return string[]
     */
    public function permissionsForRole(string $roleName): array;

    /**
     * All known role definitions, keyed by role name. Each entry may carry:
     *   'title' => display label,
     *   'level' => integer hierarchy level (0 = superadmin; lower = more power),
     *
     * @return array<string,array>
     */
    public function allRoles(): array;

    /**
     * Raw ACL rule rows (see Kodhe\Framework\Auth\Acl for the expected
     * shape). May return an empty array when no explicit rules exist.
     *
     * @return array<int,array>
     */
    public function aclRules(): array;
}
