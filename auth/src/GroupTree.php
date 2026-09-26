<?php

declare(strict_types=1);

namespace Kodhe\Framework\Auth;

/**
 * Hierarchical user groups (a tree).
 *
 * Groups let an application partition users ("news", "news/editors",
 * "sales/eu/berlin") and let ACL rules address a node either exactly or
 * including everything beneath it. Nodes are addressed by name path
 * ("root/child/grandchild"), which is stable across renames of ids.
 *
 * The tree itself is pure in-memory structure — persistence lives in the
 * provider (see Contracts\AuthorizableProviderInterface::groupsForUser()
 * and groupTree()). A guard builds one lazily from configuration:
 *
 *   'groups' => [
 *       'root'    => [],                       // optional explicit root
 *       'news'    => ['parent' => 'root'],
 *       'editors' => ['parent' => 'news', 'members' => [3, 7]],
 *       'eu'      => ['parent' => 'sales'],
 *   ],
 *
 * Membership semantics:
 *  - memberOf($user, 'news')          exact node membership;
 *  - memberOf($user, 'news', subtree) when $subtree = true, membership in ANY
 *    descendant of 'news' also counts (the classic "group hierarchy" rule);
 *  - paths() / children() / level() expose the structure for admin UIs.
 */
class GroupTree
{
    /** @var array<string,string[]> normalized node path => child paths */
    protected array $childrenMap = [];

    /** @var array<string,int|string|null> canonical node path => parent path */
    protected array $parentMap = [];

    /** @var array<string,array<int|string,bool>> canonical node path => set of user ids */
    protected array $members = [];

    /** @var array<string,string> lowercase key => canonical node path (case-insensitive lookup) */
    protected array $alias = [];

    /** @var string root marker used for top-level nodes */
    public const ROOT = '';

    /**
     * @param array<string,array|int|string> $definitions
     *        key = group name (or full path), value = shorthand (ignored) or
     *        array with 'parent' and/or 'members'.
     */
    public function __construct(array $definitions = [])
    {
        // Pre-register every declared top-level name as an alias so that a
        // definition referencing it as a parent (e.g. an explicit "root"
        // node used by ['news' => ['parent' => 'root']]) resolves to the
        // named node instead of the implicit tree root.
        foreach ($definitions as $name => $def) {
            $first = explode('/', trim((string) $name, '/@'))[0] ?? '';
            if ($first !== '') {
                $this->alias[strtolower($first)] = $first;
            }
        }

        $this->load($definitions);
    }

    /**
     * Rebuild the tree from definitions. Three shapes are accepted:
     *
     *  flat (recommended):  ['editors' => ['parent' => 'news', 'members' => [3]]]
     *  path keys:           ['news/editors' => ['members' => [3]]]
     *  nested:              ['news' => ['editors' => ['members' => [3]]]]
     *
     * A definition array is treated as "nested children" when its keys are
     * neither structural ('parent', 'members', 'users', 'id') nor numeric —
     * each such key becomes a child node of the current one.
     *
     * @param array<string,mixed> $definitions
     */
    public function load(array $definitions): void
    {
        $this->childrenMap = [];
        $this->parentMap   = [];
        $this->members     = [];

        foreach ($definitions as $name => $def) {
            $this->ingest((string) $name, $def, self::ROOT);
        }
    }

    /**
     * @param array<mixed>|scalar|null $def
     */
    protected function ingest(string $name, mixed $def, string $parentPath): void
    {
        if (!is_array($def)) {
            // 'name' => parentName (string shorthand)
            if (is_string($def) && $def !== '') {
                $this->ensure($name, $this->normalizeParent($def));
            } else {
                $this->ensure($name, $parentPath);
            }
            return;
        }

        $structural = ['parent', 'members', 'users', 'id'];
        $childKeys  = [];
        foreach (array_keys($def) as $k) {
            if (!in_array((string) $k, $structural, true)) {
                $childKeys[] = (string) $k;
            }
        }

        $path = $this->ensure($name, $this->normalizeParent((string) ($def['parent'] ?? $parentPath)));

        foreach ((array) ($def['members'] ?? $def['users'] ?? []) as $uid) {
            if ($uid !== null && $uid !== '') {
                $this->addMember($path, $uid);
            }
        }

        foreach ($childKeys as $child) {
            $this->ingest($child, $def[$child], $path);
        }
    }

    protected function normalizeParent(string $parent): string
    {
        if ($parent === '') {
            return self::ROOT;
        }
        // A declared top-level name wins (e.g. an explicit "root" node);
        // otherwise the reserved word "root" means the implicit tree root.
        $alias = strtolower($parent);
        if (isset($this->alias[$alias])) {
            return $this->alias[$alias];
        }
        if (strcasecmp($parent, 'root') === 0) {
            return self::ROOT;
        }
        return $parent;
    }

    /**
     * Create (or fetch) a node.
     *
     * @param string $nameOrPath bare group name ("editors") or an explicit
     *                           path ("news/editors" or "@root/news/editors"
     *                           where the leading segment is the tree root).
     * @param string $parentPath path of the parent node ('' = top level);
     *                           ignored when $nameOrPath already is a path.
     *
     * @return string the canonical path of the node
     */
    public function ensure(string $nameOrPath, string $parentPath = self::ROOT): string
    {
        $raw  = ltrim($nameOrPath, '/@');
        $path = $this->resolvePath($raw, $parentPath);

        if (isset($this->parentMap[$path])) {
            return $path;
        }

        // Register every missing ancestor along the path.
        $segments = explode('/', $path);
        $built    = '';
        foreach ($segments as $i => $seg) {
            $full = $i === 0 ? $seg : $built . '/' . $seg;
            if (!isset($this->parentMap[$full])) {
                $this->parentMap[$full]   = $i === 0 ? self::ROOT : $built;
                $this->childrenMap[$full] = [];
                $this->members[$full]     = [];
                $this->childrenMap[$i === 0 ? self::ROOT : $built][] = $full;
                $this->alias[strtolower($full)] = $full;
                if ($i === 0) {
                    $this->alias[strtolower($seg)] = $seg;
                }
            }
            $built = $full;
        }
        return $path;
    }

    /**
     * Turn a raw key into a canonical path. A multi-segment key whose first
     * segment matches a declared top-level node is treated as a full path;
     * otherwise it is attached under $parentPath. Single-segment keys always
     * attach under $parentPath (so 'editors' + parent 'news' => 'news/editors').
     */
    protected function resolvePath(string $raw, string $parentPath = self::ROOT): string
    {
        if ($raw === '') {
            return $parentPath;
        }
        if (!str_contains($raw, '/')) {
            return $parentPath === self::ROOT ? $raw : $parentPath . '/' . $raw;
        }
        $first = substr($raw, 0, strpos($raw, '/'));
        // Already anchored at a declared top-level node? Use as-is.
        if (isset($this->alias[strtolower($first)])) {
            return $raw;
        }
        // Otherwise interpret "name/child..." relative to the parent chain:
        // attach the whole key under $parentPath.
        if ($parentPath === self::ROOT) {
            return $raw;
        }
        return $parentPath . '/' . $raw;
    }

    /**
     * Resolve any user-supplied name/path to its canonical form ('' when the
     * implicit root is meant). Unknown keys are returned normalised so that
     * lookups simply miss.
     */
    public function canonical(string $nameOrPath): string
    {
        $raw = ltrim($nameOrPath, '/@');
        if ($raw === '') {
            return self::ROOT;
        }
        if (isset($this->parentMap[$raw])) {
            return $raw;
        }
        // exact-case miss → try case-insensitive alias of the FULL key
        $alias = strtolower($raw);
        if (isset($this->alias[$alias])) {
            return $this->alias[$alias];
        }
        // Multi-segment path whose head anchors on a known top-level node.
        if (str_contains($raw, '/')) {
            return $raw;
        }
        // Bare name shadowed by a nested node ("editors" for "news/editors").
        foreach ($this->parentMap as $p => $_) {
            if (str_contains($p, '/') && substr($p, strrpos($p, '/') + 1) === $raw) {
                return $p;
            }
        }
        return $raw;
    }

    /**
     * Canonicalise a group reference coming from ACL rules or scopes. Unlike
     * canonical() this never creates/attaches anything: unknown names are
     * returned normalised so lookups simply miss (fail closed).
     */
    protected function canonicalGroup(string $group): string
    {
        $raw = ltrim($group, '/@');
        if ($raw === '' || strcasecmp($raw, 'root') === 0) {
            // "root" is the implicit tree root unless declared as a node.
            return $this->exists('root') ? 'root' : self::ROOT;
        }
        return $this->canonical($raw);
    }

    public function exists(string $nameOrPath): bool
    {
        return isset($this->parentMap[$this->canonical($nameOrPath)]);
    }

    public function parent(string $path): ?string
    {
        $p = $this->parentMap[$this->canonical($path)] ?? null;
        return ($p === self::ROOT || $p === null) ? null : $p;
    }

    /**
     * @return string[] direct child paths
     */
    public function children(string $path = self::ROOT): array
    {
        return $this->childrenMap[$this->canonical($path)] ?? [];
    }

    /**
     * Depth of a node (top-level = 0).
     */
    public function level(string $path): int
    {
        $path = $this->canonical($path);
        return $this->exists($path) ? substr_count($path, '/') : -1;
    }

    /**
     * All node paths in the tree.
     *
     * @return string[]
     */
    public function paths(): array
    {
        return array_keys($this->parentMap);
    }

    // ------------------------------------------------------------------
    // Membership
    // ------------------------------------------------------------------

    public function addMember(string $path, int|string $userId): void
    {
        $path = $this->ensure($path);
        $this->members[$path][(string) $userId] = true;
    }

    public function removeMember(string $path, int|string $userId): bool
    {
        $path = $this->canonical($path);
        if (!isset($this->members[$path][(string) $userId])) {
            return false;
        }
        unset($this->members[$path][(string) $userId]);
        return true;
    }

    /**
     * Direct members of one node (not descendants).
     *
     * @return array<int,string> user ids as strings
     */
    public function members(string $path = self::ROOT): array
    {
        return array_keys($this->members[$this->canonical($path)] ?? []);
    }

    /**
     * Members of a node AND all its descendants.
     *
     * @return string[] unique user ids
     */
    public function membersIncludingSubtree(string $path = self::ROOT): array
    {
        $out = [];
        $stack = [$this->canonical($path)];
        while ($stack !== []) {
            $node = array_pop($stack);
            foreach (array_keys($this->members[$node] ?? []) as $uid) {
                $out[$uid] = true;
            }
            foreach ($this->children($node) as $child) {
                $stack[] = $child;
            }
        }
        return array_keys($out);
    }

    /**
     * Is $userId attached to $group? With $subtree = true, belonging to any
     * descendant of $group also matches.
     */
    public function memberOf(int|string|null $userId, string $group, bool $subtree = true): bool
    {
        if ($userId === null) {
            return false;
        }
        $group = $this->canonicalGroup($group);
        if (!$this->exists($group)) {
            return false;
        }
        if (isset($this->members[$group][(string) $userId])) {
            return true;
        }
        if (!$subtree) {
            return false;
        }
        foreach ($this->descendantPaths($group) as $d) {
            if (isset($this->members[$d][(string) $userId])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Every group path the user belongs to (ancestors included when the user
     * sits on a leaf: returning the leaf only would hide seniority context).
     *
     * @return string[]
     */
    public function groupsOf(int|string|null $userId, bool $includeAncestors = true): array
    {
        if ($userId === null) {
            return [];
        }
        $hits = [];
        foreach ($this->members as $path => $set) {
            if (isset($set[(string) $userId])) {
                $hits[] = $path;
            }
        }
        if ($includeAncestors) {
            foreach ($hits as $path) {
                for ($p = $this->parent($path); $p !== null; $p = $this->parent($p)) {
                    $hits[] = $p;
                }
            }
        }
        return array_values(array_unique($hits));
    }

    /**
     * @return string[] all strict descendants of $path
     */
    public function descendantPaths(string $path = self::ROOT): array
    {
        $out   = [];
        		$stack = $this->children($path);
        while ($stack !== []) {
            $node = array_pop($stack);
            $out[] = $node;
            foreach ($this->children($node) as $child) {
                $stack[] = $child;
            }
        }
        return $out;
    }

    /**
     * Whether $group (or any descendant of it) exists as a node — used by
     * Acl when the scope carries a whole-tree context object.
     */
    public function subtreeHas(string $group): bool
    {
        $group = $this->canonicalGroup($group);
        if ($this->exists($group)) {
            return true;
        }
        foreach ($this->paths() as $p) {
            if (str_starts_with($p, $group . '/')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Adapter used by Acl scope matching: does this tree place $userId inside
     * $group (subtree-aware)?
     */
    public function memberBelongs(int|string|null $userId, string $group): bool
    {
        return $this->memberOf($userId, $group, true);
    }
}
