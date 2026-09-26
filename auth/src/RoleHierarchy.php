<?php

declare(strict_types=1);

namespace Kodhe\Framework\Auth;

/**
 * Role hierarchy: roles are ordered by an integer "level" where LOWER means
 * MORE POWER (0 = superadmin). A role implicitly carries every permission of
 * all roles beneath it in the tree, and may only act on users whose effective
 * level is strictly deeper (less power) than the actor's best level.
 *
 * The hierarchy is defined through configuration — no storage needed:
 *
 *   'roles' => [
 *       'superadmin' => ['level' => 0],
 *       'admin'      => ['level' => 1, 'inherits' => ['manager']],
 *       'manager'    => ['level' => 2],
 *       'editor'     => ['level' => 3],
 *       'user'       => ['level' => 4],
 *   ],
 *
 * Two ways to express inheritance:
 *  - implicit: any role with a higher level is "beneath" a lower-level role
 *    when both share the same branch root (see build());
 *  - explicit: the 'inherits' list pins exactly which child roles a parent
 *    absorbs. When at least one role declares 'inherits', the explicit graph
 *    is authoritative for permission expansion.
 */
class RoleHierarchy
{
    /** @var array<string,int> role => level */
    protected array $levels = [];

    /** @var array<string,string[]> role => direct children it inherits from */
    protected array $children = [];

    /** @var array<string,string[]> cached role => full transitive closure */
    protected array $closure = [];

    /**
     * @param array<string,array|int> $roles role name => definition
     *        (int shorthand = level; array may carry 'level' and 'inherits').
     */
    public function __construct(array $roles = [])
    {
        $this->build($roles);
    }

    public function build(array $roles): void
    {
        $this->levels   = [];
        $this->children = [];
        $this->closure  = [];

        foreach ($roles as $name => $def) {
            $name = (string) $name;
            if (is_int($def)) {
                $def = ['level' => $def];
            }
            if (!is_array($def)) {
                continue;
            }
            $this->levels[$name] = (int) ($def['level'] ?? 99);
            $kids = [];
            foreach ((array) ($def['inherits'] ?? []) as $kid) {
                $kid = (string) $kid;
                if ($kid !== '' && $kid !== $name) {
                    $kids[] = $kid;
                }
            }
            $this->children[$name] = $kids;
        }
    }

    public function known(string $role): bool
    {
        return array_key_exists($role, $this->levels);
    }

    public function level(string $role): ?int
    {
        return $this->levels[$role] ?? null;
    }

    /**
     * Every role whose permissions $role also holds: itself + transitive
     * 'inherits' closure + (when no explicit graph exists) every role with a
     * strictly deeper level, which the hierarchy treats as subordinate.
     *
     * @return string[]
     */
    public function expandsTo(string $role): array
    {
        if (isset($this->closure[$role])) {
            return $this->closure[$role];
        }

        $set = [$role];

        // Explicit inheritance graph (transitive, cycle-safe).
        if ($this->children !== []) {
            $stack = [$role];
            $seen  = [$role => true];
            while ($stack !== []) {
                $current = array_pop($stack);
                foreach ($this->children[$current] ?? [] as $kid) {
                    if (!isset($seen[$kid]) && $this->known($kid)) {
                        $seen[$kid] = true;
                        $set[]      = $kid;
                        $stack[]    = $kid;
                    }
                }
            }
        }

        // Implicit level ordering: a role absorbs all deeper-level roles.
        $myLevel = $this->levels[$role] ?? null;
        if ($myLevel !== null) {
            foreach ($this->levels as $other => $level) {
                if ($level > $myLevel && !in_array($other, $set, true)) {
                    $set[] = $other;
                }
            }
        }

        return $this->closure[$role] = $set;
    }

    /**
     * Effective level of a set of roles = the strongest (lowest) level.
     * Unknown roles fall back to PHP_INT_MAX so they never outrank anything.
     */
    public function bestLevel(array $roles): int
    {
        $best = PHP_INT_MAX;
        foreach ($roles as $r) {
            $lvl = $this->levels[(string) $r] ?? null;
            if ($lvl !== null && $lvl < $best) {
                $best = $lvl;
            }
        }
        return $best;
    }

    /**
     * Whether a user holding $actorRoles may act on a user holding
     * $targetRoles: strictly more power required (equal levels are peers,
     * and peers cannot manage each other).
     */
    public function canActOn(array $actorRoles, array $targetRoles): bool
    {
        $actor  = $this->bestLevel($actorRoles);
        $target = $this->bestLevel($targetRoles);
        return $actor < $target;
    }

    /**
     * @return array<string,int>
     */
    public function levels(): array
    {
        return $this->levels;
    }
}
