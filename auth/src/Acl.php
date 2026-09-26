<?php

declare(strict_types=1);

namespace Kodhe\Framework\Auth;

/**
 * Access-control list with MULTI-RULE resolution.
 *
 * A rule is an associative array:
 *   target   string   permission pattern, e.g. "forum.edit", "forum.*", "*"
 *   subject  ?string  user id ("42"), role name ("admin"), group name
 *                     ("group:editors"), "@root/editors" path shorthand or
 *                     "*"; null = any
 *   effect   string   'allow' | 'deny'
 *   priority int      higher wins; ties resolved by specificity then order
 *   scope    ?array   extra conditions matched against context keys,
 *                     e.g. ['own' => true], ['section' => 'news'] or
 *                     ['group' => 'editors'] (group membership / subtree)
 *   enabled  bool     default true
 *   expires  ?int     unix timestamp; expired rules are ignored
 *
 * Resolution order for a request (subject + permission + context):
 *   1. only enabled, non-expired rules whose subject & scope match;
 *   2. sort by priority DESC, then specificity DESC (explicit user > exact
 *      role > wildcarded pattern), then declaration order ASC;
 *   3. first matching rule decides allow/deny;
 *   4. no matching rule  => $default (fail-closed deny unless configured).
 *
 * Wildcards: '*' matches everything, trailing 'edit.*' matches 'edit.anything'.
 * A leading '!' on target or subject negates the match.
 */
class Acl
{
    public const ALLOW = 'allow';
    public const DENY  = 'deny';

    /** @var array<int,array> normalized rules */
    protected array $rules = [];

    /** @var bool what happens when NO rule matches */
    protected bool $default;

    /** @var callable|null fn(string $group, int|string|null $userId): bool — group membership test */
    protected $groupResolver = null;

    /**
     * @param array<int,array>            $rules
     * @param bool                          $default       fail-closed unless true
     * @param callable|null                 $groupResolver fn(groupName, userId): bool
     *        Used when a rule subject/scope references a GROUP. The callback
     *        receives the (possibly path-form) group name and should return
     *        whether the user belongs to it (implementations may resolve
     *        '@root/parents/child' paths by checking the node AND its subtree).
     */
    public function __construct(array $rules = [], bool $default = false, ?callable $groupResolver = null)
    {
        $this->default        = $default;
        $this->groupResolver  = $groupResolver;
        $this->load($rules);
    }

    /**
     * Swap the group-membership resolver after construction.
     */
    public function setGroupResolver(?callable $resolver): static
    {
        $this->groupResolver = $resolver;
        return $this;
    }

    /**
     * Replace all rules.
     *
     * @param array<int,array> $rules
     */
    public function load(array $rules): void
    {
        $this->rules = [];
        foreach ($rules as $rule) {
            $this->addRule($rule);
        }
    }

    /**
     * Append one rule; returns its index (usable as an id for remove()).
     */
    public function addRule(array $rule): int
    {
        $normalized = [
            'target'   => (string) ($rule['target'] ?? ($rule['permission'] ?? '*')),
            'subject'  => isset($rule['subject']) && $rule['subject'] !== null
                ? (string) $rule['subject']
                : '*',
            'effect'   => strtolower((string) ($rule['effect'] ?? self::ALLOW)) === self::DENY
                ? self::DENY
                : self::ALLOW,
            'priority' => (int) ($rule['priority'] ?? 0),
            'scope'    => (array) ($rule['scope'] ?? []),
            'enabled'  => (bool) ($rule['enabled'] ?? true),
            'expires'  => isset($rule['expires']) ? (int) $rule['expires'] : null,
        ];
        $this->rules[] = $normalized;
        return count($this->rules) - 1;
    }

    public function removeRule(int $index): bool
    {
        if (!isset($this->rules[$index])) {
            return false;
        }
        unset($this->rules[$index]);
        $this->rules = array_values($this->rules);
        return true;
    }

    /**
     * @return array<int,array>
     */
    public function rules(): array
    {
        return $this->rules;
    }

    /**
     * Core multi-rule check.
     *
     * @param int|string|null $userId    authenticated user id (null = guest)
     * @param string          $permission requested permission, e.g. "post.delete"
     * @param string[]        $roles      role names held by the user
     * @param array           $context    free-form keys for scope matching
     *                                    ('own', 'section', 'group', ... — plus
     *                                    'owner_id'/'resource_id' so the built-in
     *                                    'own' scope can resolve automatically)
     */
    public function isAllowed(
        int|string|null $userId,
        string $permission,
        array $roles = [],
        array $context = [],
        ?int $now = null
    ): bool {
        $now ??= time();

        $candidates = [];
        foreach ($this->rules as $order => $rule) {
            if (!$rule['enabled']) {
                continue;
            }
            if ($rule['expires'] !== null && $rule['expires'] < $now) {
                continue;
            }
            if (!$this->subjectMatches($rule['subject'], $userId, $roles)) {
                continue;
            }
            if (!$this->targetMatches($rule['target'], $permission)) {
                continue;
            }
            if (!$this->scopeMatches($rule['scope'], $userId, $context)) {
                continue;
            }
            $rule['_order']      = $order;
            $rule['_specificity'] = $this->specificity($rule);
            $candidates[]        = $rule;
        }

        if ($candidates === []) {
            return $this->default;
        }

        usort($candidates, static function (array $a, array $b): int {
            return ($b['priority'] <=> $a['priority'])
                ?: ($b['_specificity'] <=> $a['_specificity'])
                ?: ($a['_order'] <=> $b['_order']);
        });

        return $candidates[0]['effect'] === self::ALLOW;
    }

    /**
     * Explain resolution (debugging aid): every rule that matched, in the
     * order they were considered, plus the final decision.
     *
     * @return array{matched: array<int,array>, allowed: bool, via: ?array}
     */
    public function explain(
        int|string|null $userId,
        string $permission,
        array $roles = [],
        array $context = [],
        ?int $now = null
    ): array {
        $now ??= time();
        $matched = [];
        foreach ($this->rules as $order => $rule) {
            if (!$rule['enabled']) {
                continue;
            }
            if ($rule['expires'] !== null && $rule['expires'] < $now) {
                continue;
            }
            if ($this->subjectMatches($rule['subject'], $userId, $roles)
                && $this->targetMatches($rule['target'], $permission)
                && $this->scopeMatches($rule['scope'], $userId, $context)) {
                $rule['_order']       = $order;
                $rule['_specificity'] = $this->specificity($rule);
                $matched[]            = $rule;
            }
        }
        usort($matched, static function (array $a, array $b): int {
            return ($b['priority'] <=> $a['priority'])
                ?: ($b['_specificity'] <=> $a['_specificity'])
                ?: ($a['_order'] <=> $b['_order']);
        });
        $winner = $matched[0] ?? null;
        return [
            'matched' => $matched,
            'allowed' => $winner === null ? $this->default : $winner['effect'] === self::ALLOW,
            'via'     => $winner,
        ];
    }

    // ------------------------------------------------------------------
    // Matchers
    // ------------------------------------------------------------------

    protected function subjectMatches(string $subject, int|string|null $userId, array $roles): bool
    {
        $negate = str_starts_with($subject, '!');
        $value  = $negate ? substr($subject, 1) : $subject;

        // Group subjects: "group:editors" or path form "@root/parents/child".
        if (($group = self::extractGroup($value)) !== null) {
            $hit = $this->groupMatches($group, $userId);
            return $negate ? !$hit : $hit;
        }

        $hit = match (true) {
            $value === '*'  => true,
            $value === '?'  => $userId === null,               // guests only
            $value === '%'  => $userId !== null,               // any logged-in user
            $userId !== null && $value === (string) $userId => true, // explicit user id
            in_array($value, array_map('strval', $roles), true) => true, // role name
            default => false,
        };

        return $negate ? !$hit : $hit;
    }

    /**
     * Recognise the group forms inside a subject string.
     *
     * @return string|null bare group name ("editors", "news.team") or null
     */
    public static function extractGroup(string $subject): ?string
    {
        if (str_starts_with($subject, 'group:')) {
            $name = substr($subject, 6);
            return $name === '' ? null : $name;
        }
        if (str_starts_with($subject, '@')) {
            $path = substr($subject, 1);
            // "@root/parent/child": keep everything after the root segment so
            // resolvers can walk the tree by path; bare "@editors" works too.
            $parts = explode('/', $path);
            if (count($parts) > 2) {
                return implode('/', array_slice($parts, 1));
            }
            return $path === '' ? null : $path;
        }
        return null;
    }

    /**
     * Group membership check for a rule subject.
     *
     * Resolution order:
     *  1. the injected callable resolver (authoritative — may implement
     *     hierarchical subtree logic);
     *  2. context-free fallback: roles that look like groups ("group:x").
     * With no resolver and no matching pseudo-role group the subject simply
     * does not match (fail closed).
     */
    protected function groupMatches(string $group, int|string|null $userId): bool
    {
        if ($this->groupResolver !== null) {
            try {
                return (bool) ($this->groupResolver)($group, $userId);
            } catch (\Throwable) {
                return false; // never let a broken resolver grant access
            }
        }
        return false;
    }

    protected function targetMatches(string $target, string $permission): bool
    {
        $negate = str_starts_with($target, '!');
        $value  = $negate ? substr($target, 1) : $target;

        $hit = $this->wildmatch($value, $permission);

        return $negate ? !$hit : $hit;
    }

    /**
     * '*' global wildcard, 'x.*' prefix wildcard, '?' single segment.
     */
    protected function wildmatch(string $pattern, string $subject): bool
    {
        if ($pattern === '*') {
            return true;
        }
        if (!str_contains($pattern, '*') && !str_contains($pattern, '?')) {
            return $pattern === $subject;
        }
        $regex = preg_quote($pattern, '#');
        $regex = strtr($regex, ['\\*' => '(.*)', '\\?' => '[^.]+']);
        return (bool) preg_match('#^' . $regex . '$#', $subject);
    }

    /**
     * Scope conditions must ALL hold. Supported keys:
     *  - 'own' => true         actor must own the resource (context owner_id
     *                          == user id, or context['own'] === true)
     *  - 'group' => 'x'        actor belongs to group x (via the resolver;
     *                          falls back to context['groups'] membership,
     *                          subtree-aware when the entry is a GroupTree)
     *  - any other key         equality (or wildcard match) against context
     */
    protected function scopeMatches(array $scope, int|string|null $userId, array $context): bool
    {
        foreach ($scope as $key => $want) {
            if ($key === 'own') {
                $isOwn = ($context['own'] ?? false) === true;
                if (!$isOwn
                    && $userId !== null
                    && isset($context['owner_id'], $context['resource_id']) === false
                    && isset($context['owner_id'])
                    && (string) $context['owner_id'] === (string) $userId) {
                    $isOwn = true;
                }
                if ($want === true && !$isOwn) {
                    return false;
                }
                if ($want === false && $isOwn) {
                    return false;
                }
                continue;
            }
            if ($key === 'group') {
                $group = (string) (self::extractGroup((string) $want) ?? $want);
                if (!$this->groupInContext($group, $userId, $context)) {
                    return false;
                }
                continue;
            }
            $have = $context[$key] ?? null;
            if (is_string($want) && (str_contains($want, '*') || str_contains($want, '?'))) {
                if (!is_scalar($have) || !$this->wildmatch($want, (string) $have)) {
                    return false;
                }
                continue;
            }
            if ($have != $want) { // loose: "1" matches 1 from DB drivers
                return false;
            }
        }
        return true;
    }

    /**
     * Group-scope check: resolver first, then context['groups'] list. When
     * that list holds GroupTree nodes, subtree membership is honoured.
     */
    protected function groupInContext(string $group, int|string|null $userId, array $context): bool
    {
        if ($this->groupResolver !== null) {
            try {
                if ((bool) ($this->groupResolver)($group, $userId)) {
                    return true;
                }
            } catch (\Throwable) {
                return false;
            }
            // Resolver said no — still allow an explicit context override so
            // callers can assert membership for the current request.
        }
        $held = $context['groups'] ?? [];
        if (!is_array($held)) {
            $held = self::toList($held);
        }
        foreach ($held as $item) {
            if ($item instanceof GroupTree) {
                if ($item->memberBelongs($userId, $group) || $item->subtreeHas($group)) {
                    return true;
                }
                continue;
            }
            if ((string) $item === $group) {
                return true;
            }
            // Hierarchical names in plain lists: "editors" matches "news/editors".
            $parts = explode('/', (string) $item);
            if (in_array($group, $parts, true) || end($parts) === $group) {
                return true;
            }
        }
        return false;
    }

    /**
     * Normalize a scalar/array/comma-string into a trimmed string list.
     *
     * @return string[]
     */
    protected static function toList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        $items = is_array($value) ? $value : (preg_split('/[,\\s]+/', (string) $value) ?: []);
        $out   = [];
        foreach ($items as $item) {
            $item = trim((string) $item);
            if ($item !== '') {
                $out[] = $item;
            }
        }
        return $out;
    }

    /**
     * Specificity score used to break equal-priority ties:
     * explicit user id beats role beats '*', exact target beats wildcards.
     */
    protected function specificity(array $rule): int
    {
        $score = 0;
        $subject = $rule['subject'];
        if (self::extractGroup(ltrim($subject, '!')) !== null) {
            $score += 3; // group subject: between role (2) and explicit user id (4)
        } elseif ($subject !== '*' && $subject !== '?' && $subject !== '%') {
            $score += ctype_digit(ltrim($subject, '!')) ? 4 : 2;
        } elseif ($subject === '?' || $subject === '%') {
            $score += 1;
        }
        $target = $rule['target'];
        if (!str_contains($target, '*')) {
            $score += 8;
        } elseif ($target !== '*') {
            $score += 4; // prefix wildcard still more specific than global
        }
        $score += count($rule['scope']) * 2;
        return $score;
    }
}
