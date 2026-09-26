<?php

declare(strict_types=1);

// Runtime verification harness for the auth features: registration,
// password change/reset, email verification, throttling, events, role
// hierarchy, multi-rule ACL and guard-level authorization.
// Run: php auth/tests/features_test.php   (exit 0 = all green)

error_reporting(E_ALL);

$base = dirname(__DIR__) . '/src/';
require_once $base . 'AuthInterface.php';
require_once $base . 'Contracts/UserProviderInterface.php';
require_once $base . 'Contracts/RegisterableProviderInterface.php';
require_once $base . 'Contracts/UpdatableProviderInterface.php';
require_once $base . 'Contracts/AuthEventDispatcherInterface.php';
require_once $base . 'Contracts/AuthorizableProviderInterface.php';
require_once $base . 'RoleHierarchy.php';
require_once $base . 'GroupTree.php';
require_once $base . 'Acl.php';
require_once $base . 'AuthEvents.php';
require_once $base . 'AuthException.php';
require_once $base . 'Auth.php';
require_once $base . 'Support/Facade.php';

use Kodhe\Framework\Auth\Acl;
use Kodhe\Framework\Auth\Auth;
use Kodhe\Framework\Auth\AuthEvents;
use Kodhe\Framework\Auth\AuthException;
use Kodhe\Framework\Auth\Contracts\AuthEventDispatcherInterface;
use Kodhe\Framework\Auth\Contracts\AuthorizableProviderInterface;
use Kodhe\Framework\Auth\Contracts\RegisterableProviderInterface;
use Kodhe\Framework\Auth\Contracts\UpdatableProviderInterface;
use Kodhe\Framework\Auth\Contracts\UserProviderInterface;
use Kodhe\Framework\Auth\Facade;
use Kodhe\Framework\Auth\GroupTree;
use Kodhe\Framework\Auth\RoleHierarchy;

// --- fakes -------------------------------------------------------------

class FakeSession
{
    public array $data = [];
    public function userdata($key = null) { return $key === null ? $this->data : ($this->data[$key] ?? null); }
    public function set_userdata(array $d): void { $this->data = array_merge($this->data, $d); }
    public function unset_userdata($keys): void { foreach ((array) $keys as $k) { unset($this->data[$k]); } }
}

class RecordingDispatcher implements AuthEventDispatcherInterface
{
    public array $events = [];
    public bool $throwOnce = false;
    public function dispatch(string $event, array $payload = []): void
    {
        if ($this->throwOnce) { $this->throwOnce = false; throw new \RuntimeException('boom'); }
        $this->events[] = $event;
    }
}

class FullProvider implements UserProviderInterface, RegisterableProviderInterface, UpdatableProviderInterface
{
    /** @var array<int,array> */
    public array $users = [];
    public int $next = 1;

    public function seed(string $email, string $plain, array $extra = []): int
    {
        $id = $this->next++;
        $this->users[$id] = array_merge(['id' => $id, 'email' => $email, 'password' => password_hash($plain, PASSWORD_BCRYPT, ['cost' => 4])], $extra);
        return $id;
    }
    public function retrieveByIdentifier(string $identifier, string $value, array $extra = []): ?array
    {
        foreach ($this->users as $u) {
            if (($u[$identifier] ?? null) === $value) {
                foreach ($extra as $k => $v) { if (($u[$k] ?? null) != $v) { return null; } }
                return $u;
            }
        }
        return null;
    }
    public function retrieveById(int|string $id): ?array { return $this->users[(int) $id] ?? null; }
    public function updateRememberToken(int|string $id, ?string $token): void { $this->users[(int) $id]['remember_token'] = $token; }
    public function hasIdentifier(string $identifier, string $value): bool { return $this->retrieveByIdentifier($identifier, $value) !== null; }
    public function createUser(array $attributes, string $passwordHash): int|string
    {
        $id = $this->next++;
        $this->users[$id] = array_merge($attributes, ['id' => $id, 'password' => $passwordHash]);
        return $id;
    }
    public function updateUser(int|string $id, array $columns): void
    {
        foreach ($columns as $k => $v) { $this->users[(int) $id][$k] = $v; }
    }
}

// Build guard wired to fakes via reflection (session is resolved internally).
function makeGuard(UserProviderInterface $provider, FakeSession $session, array $cfg = []): Auth
{
    $auth = new Auth(array_merge([
        'algo' => PASSWORD_BCRYPT,
        'options' => ['cost' => 4],
    ], $cfg), $provider);
    $r = new ReflectionObject($auth);
    $p = $r->getProperty('session'); $p->setAccessible(true); $p->setValue($auth, $session);
    return $auth;
}

$pass = 0; $fail = 0;
function check(string $name, bool $cond): void {
    global $pass, $fail;
    if ($cond) { $pass++; echo "PASS  $name\n"; } else { $fail++; echo "FAIL  $name\n"; }
}

// ---------------------------------------------------------------------

$prov = new FullProvider();
$id = $prov->seed('a@a.com', 'secret', ['is_active' => 1]);

// 1. register()
$session = new FakeSession();
$auth = makeGuard($prov, $session, ['register_columns' => ['name']]);
$newId = $auth->register(['email' => 'new@x.com', 'password' => 'hunter2', 'name' => 'Newbie', 'evil_role' => 'admin']);
check('register returns id', $newId === $prov->next - 1);
check('register stores identifier + whitelisted columns only',
    $prov->users[$newId]['email'] === 'new@x.com'
    && $prov->users[$newId]['name'] === 'Newbie'
    && !array_key_exists('evil_role', $prov->users[$newId]));
check('register hashes password', str_starts_with($prov->users[$newId]['password'], '$2y$'));
try { $auth->register(['email' => 'new@x.com', 'password' => 'x']); check('register duplicate throws', false); }
catch (AuthException) { check('register duplicate throws', true); }
try { $auth->register(['email' => 'z@z.com']); check('register empty password throws', false); }
catch (AuthException) { check('register empty password throws', true); }

// 2. attempt + throttle + events
$disp = new RecordingDispatcher();
$auth->setEventDispatcher($disp);
for ($i = 0; $i < 5; $i++) { check("failed attempt #$i", $auth->attempt('a@a.com', 'wrong') === false); }
check('throttle engaged after max_attempts', $auth->tooManyAttempts('a@a.com'));
check('retryAfter > 0 while locked', $auth->retryAfter('a@a.com') > 0);
check('correct password blocked while throttled', $auth->attempt('a@a.com', 'secret') === false);
$auth->clearFailedAttempts('a@a.com');
check('clearFailedAttempts unlocks then login works', $auth->attempt('a@a.com', 'secret') === true);
check('events include failed+login', in_array(AuthEvents::FAILED, $disp->events) && in_array(AuthEvents::LOGIN, $disp->events));
check('user() has no hash after login', !array_key_exists('password', (array) $auth->user()));

// broken dispatcher must not break auth
$disp->throwOnce = true;
$auth->logout();
check('listener exception swallowed on logout', true);

// 3. changePassword
$auth->login($prov->retrieveById($id));
check('changePassword rejects wrong current', $auth->changePassword('nope', 'brandnew') === false);
check('changePassword accepts right current', $auth->changePassword('secret', 'brandnew') === true);
check('new password stored hashed & verifiable', password_verify('brandnew', $prov->users[$id]['password']));

// 4. password reset flow
$tok = $auth->sendPasswordReset('a@a.com');
check('sendPasswordReset returns 64-hex token', is_string($tok) && preg_match('/^[a-f0-9]{64}$/', $tok));
check('only sha256(token) persisted', $prov->users[$id]['password_reset_hash'] === hash('sha256', $tok));
check('unknown account returns null', $auth->sendPasswordReset('ghost@x.com') === null);
check('reset with bad token fails', $auth->resetPassword('a@a.com', str_repeat('0', 64), 'x') === false);
check('reset with good token works', $auth->resetPassword('a@a.com', $tok, 'fresh-pw') === true);
check('password updated by reset', password_verify('fresh-pw', $prov->users[$id]['password']));
check('token single-use (cleared)', $prov->users[$id]['password_reset_hash'] === null);
check('remember token revoked on reset', ($prov->users[$id]['remember_token'] ?? '') === '');

// expired token
$auth->sendPasswordReset('a@a.com');
$prov->users[$id]['password_reset_expires'] = time() - 1;
check('expired reset token rejected', $auth->resetPassword('a@a.com', $tok, 'zz') === false);

// 5. email verification
check('isVerified false initially', $auth->isVerified($prov->retrieveById($id)) === false);
$vTok = $auth->requestVerification('a@a.com');
check('requestVerification returns token', is_string($vTok) && strlen($vTok) === 64);
check('verify with bad token fails', $auth->verifyEmail('a@a.com', 'bogus') === false);
check('verify with good token works', $auth->verifyEmail('a@a.com', $vTok) === true);
check('email_verified_at stamped', !empty($prov->users[$id]['email_verified_at']));
check('isVerified true now', $auth->isVerified($prov->retrieveById($id)) === true);
check('re-verify idempotent', $auth->verifyEmail('a@a.com', $vTok) === true);

// 6. register auto-login
$prov2 = new FullProvider();
$auth2 = makeGuard($prov2, new FakeSession(), ['register_auto_login' => true]);
$nid = $auth2->register(['email' => 'auto@x.com', 'password' => 'pw12345']);
check('auto-login after register', $auth2->check() && $auth2->id() === $nid);

// 7. Facade passthrough of new methods exists
foreach (['register','changePassword','sendPasswordReset','resetPassword','requestVerification','verifyEmail','isVerified','attempts','retryAfter','tooManyAttempts','clearFailedAttempts'] as $m) {
    check("Facade::$m defined", method_exists(Facade::class, $m));
}

// 8. provider lacking optional contracts -> AuthException
$readOnly = new class implements UserProviderInterface {
    public function retrieveByIdentifier(string $i, string $v, array $e = []): ?array { return null; }
    public function retrieveById(int|string $id): ?array { return null; }
    public function updateRememberToken(int|string $id, ?string $t): void {}
};
$authRO = new Auth([], $readOnly);
$r = new ReflectionObject($authRO); $p = $r->getProperty('session'); $p->setAccessible(true); $p->setValue($authRO, new FakeSession());
try { $authRO->register(['email' => 'x@x.com', 'password' => 'y']); check('register on read-only provider throws', false); }
catch (AuthException) { check('register on read-only provider throws', true); }

// =====================================================================
// 9. Role hierarchy (unit)
// =====================================================================
$hier = new RoleHierarchy([
    'superadmin' => ['level' => 0],
    'admin'      => ['level' => 1, 'inherits' => ['manager']],
    'manager'    => ['level' => 2],
    'editor'     => ['level' => 3],
    'user'       => ['level' => 4],
]);
check('hierarchy: admin expands to manager', in_array('manager', $hier->expandsTo('admin'), true));
check('hierarchy: admin absorbs deeper levels', in_array('editor', $hier->expandsTo('admin'), true));
check('hierarchy: user does not expand upward', !in_array('admin', $hier->expandsTo('user'), true));
check('hierarchy: bestLevel of [user,manager] = 2', $hier->bestLevel(['user', 'manager']) === 2);
check('hierarchy: manager can act on editor', $hier->canActOn(['manager'], ['editor']));
check('hierarchy: editor cannot act on manager', !$hier->canActOn(['editor'], ['manager']));
check('hierarchy: peers cannot act on each other', !$hier->canActOn(['admin'], ['admin']));
check('hierarchy: unknown role never outranks', !$hier->canActOn(['ghost'], ['user']));

// =====================================================================
// 10. ACL multi-rule (unit)
// =====================================================================
$acl = new Acl([
    ['target' => '*',          'subject' => 'superadmin', 'effect' => 'allow', 'priority' => 100],
    ['target' => 'forum.*',    'subject' => 'moderator',  'effect' => 'allow'],
    ['target' => 'forum.edit', 'subject' => '*',          'effect' => 'deny',  'priority' => 5],
    ['target' => 'forum.edit', 'subject' => '7',          'effect' => 'allow', 'priority' => 5], // more specific subject wins tie
    ['target' => 'post.edit',  'subject' => '%',          'effect' => 'allow', 'scope' => ['own' => true]],
    ['target' => 'ads.*',      'subject' => '*',          'effect' => 'deny',  'expires' => time() - 10], // expired -> ignored
], default: false);
check('acl: superadmin wildcard allow', $acl->isAllowed(1, 'anything.at.all', ['superadmin']));
check('acl: moderator forum.allow', $acl->isAllowed(2, 'forum.allow', ['moderator']));
check('acl: global deny beats role allow (priority)', !$acl->isAllowed(2, 'forum.edit', ['moderator']));
check('acl: explicit-user allow wins specificity tie', $acl->isAllowed(7, 'forum.edit', ['moderator']));
check('acl: own-scope allow when owner', $acl->isAllowed(3, 'post.edit', ['user'], ['owner_id' => 3]));
check('acl: own-scope deny when not owner', !$acl->isAllowed(3, 'post.edit', ['user'], ['owner_id' => 9]));
check('acl: expired rule ignored -> default deny', !$acl->isAllowed(3, 'ads.view', ['user']));
check('acl: guest (% fails) denied', !$acl->isAllowed(null, 'post.edit', [], ['owner_id' => null]));
$expl = $acl->explain(7, 'forum.edit', ['moderator']);
check('acl: explain reports winner', $expl['allowed'] === true && $expl['via']['subject'] === '7');
check('acl: disabled rule skipped', (new Acl([
    ['target' => '*', 'subject' => '*', 'effect' => 'allow', 'enabled' => false],
]))->isAllowed(1, 'x') === false);
$aclCheck = new Acl([
    ['target' => '!secret.*', 'subject' => '*', 'effect' => 'allow', 'priority' => 1],
]);
check('acl: negation matches outside prefix', $aclCheck->isAllowed(1, 'public.view', []));
check('acl: negation excludes inside prefix', !$aclCheck->isAllowed(1, 'secret.keys', []));

// =====================================================================
// 10b. Group hierarchy (unit) — GroupTree + ACL group subjects/scopes
// =====================================================================
$tree = new GroupTree([
    'root'    => [],
    'news'    => ['parent' => 'root', 'members' => [10]],
    'editors' => ['parent' => 'news', 'members' => [11]],
    'sales'   => ['parent' => 'root'],
]);
check('groups: nodes registered', $tree->exists('news') && $tree->exists('news/editors'));
check('groups: exact membership', $tree->memberOf(10, 'news', false));
check('groups: subtree membership', $tree->memberOf(11, 'news', true) && !$tree->memberOf(11, 'news', false));
check('groups: parent()', $tree->parent('news') === 'root' && $tree->parent('news/editors') === 'news');
check('groups: children()', in_array('news', $tree->children('root'), true));
check('groups: level()', $tree->level('news') === 1 && $tree->level('news/editors') === 2);
check('groups: paths()', in_array('news/editors', $tree->paths(), true));
check('groups: groupsOf includes ancestors', in_array('news', $tree->groupsOf(11), true) && in_array('news/editors', $tree->groupsOf(11), true));
$gAcl = new Acl([
    ['target' => 'cms.*',      'subject' => '@news/editors', 'effect' => 'allow'],           // group subject via resolver
    ['target' => 'crm.view',   'subject' => '%', 'effect' => 'allow', 'scope' => ['group' => 'sales']], // group scope
], default: false, groupResolver: fn(string $g, int|string|null $u) => $tree->memberBelongs($u, $g));
check('acl: group subject allows member', $gAcl->isAllowed(11, 'cms.publish', []));
check('acl: group subject denies non-member', !$gAcl->isAllowed(10, 'cms.publish', []));
$tree->addMember('sales', 12);
check('acl: group scope allows member', $gAcl->isAllowed(12, 'crm.view', []));
check('acl: group scope denies outsider', !$gAcl->isAllowed(10, 'crm.view', []));
check('acl: context groups fallback works', (new Acl([
    ['target' => '*', 'subject' => '@beta', 'effect' => 'allow'],
]))->isAllowed(5, 'x.y', [], ['groups' => ['alpha/beta']]));

// Guard-level integration: config['groups'] feeds the ACL resolver.
$grpProv = new FullProvider();
$alice = $grpProv->seed('alice@x.com', 'pw');
$bob   = $grpProv->seed('bob@x.com', 'pw');
$grpAuth = makeGuard($grpProv, new FakeSession(), [
    'groups' => [
        'news'    => ['members' => [$alice]],
        'editors' => ['parent' => 'news', 'members' => [$bob]],
    ],
    'acl_rules' => [
        ['target' => 'cms.publish', 'subject' => '@news', 'effect' => 'allow'],
    ],
]);
$grpAuth->login($grpProv->retrieveById($bob));
check('guard: inGroup() subtree-aware', $grpAuth->inGroup($bob, 'news') && $grpAuth->inGroup($bob, 'news/editors'));
check('guard: userGroups() lists paths', in_array('news/editors', $grpAuth->userGroups(), true));
check('guard: ACL @group rule matches via tree', $grpAuth->allows('cms.publish'));
$grpAuth->login($grpProv->retrieveById($alice));
check('guard: non-member of editors denied editor-only rule', !(new Acl([
    ['target' => 'cms.publish', 'subject' => '@news/editors', 'effect' => 'allow'],
], false, fn(string $g, int|string|null $u) => $grpAuth->inGroup($u, $g)))->isAllowed($alice, 'cms.publish', []));
foreach (['groups','inGroup','userGroups'] as $m) {
    check("Facade::$m defined", method_exists(Facade::class, $m));
}

// =====================================================================
// 11. Guard-level authorization (roles + permissions + ACL via provider)
// =====================================================================
class AuthzProvider implements UserProviderInterface, AuthorizableProviderInterface
{
    public array $users = [];
    public int $next = 1;
    public array $rolePerms = [
        'admin'   => ['user.manage', 'system.config'],
        'editor'  => ['post.edit', 'post.publish'],
        'user'    => ['post.create'],
    ];
    public array $userRoles = [];   // id => [role names]
    public array $rules = [];

    public function seed(string $email, string $plain, array $extra = []): int
    {
        $id = $this->next++;
        $this->users[$id] = array_merge(['id' => $id, 'email' => $email, 'password' => password_hash($plain, PASSWORD_BCRYPT, ['cost' => 4])], $extra);
        return $id;
    }
    public function retrieveByIdentifier(string $i, string $v, array $e = []): ?array
    {
        foreach ($this->users as $u) { if (($u[$i] ?? null) === $v) { return $u; } }
        return null;
    }
    public function retrieveById(int|string $id): ?array { return $this->users[(int) $id] ?? null; }
    public function updateRememberToken(int|string $id, ?string $t): void { $this->users[(int) $id]['remember_token'] = $t; }
    public function rolesForUser(int|string $userId): array { return $this->userRoles[(int) $userId] ?? []; }
    public function permissionsForRole(string $roleName): array { return $this->rolePerms[$roleName] ?? []; }
    public function allRoles(): array { return array_map(fn($l) => ['level' => $l], ['admin' => 1, 'editor' => 2, 'user' => 3]); }
    public function aclRules(): array { return $this->rules; }
}

$azProv = new AuthzProvider();
$adminId  = $azProv->seed('admin@x.com', 'pw');
$editorId = $azProv->seed('edit@x.com', 'pw');
$plebId   = $azProv->seed('pleb@x.com', 'pw');
$peerId   = $azProv->seed('peer@x.com', 'pw');
$azProv->userRoles = [$adminId => ['admin'], $editorId => ['editor'], $plebId => ['user'], $peerId => ['user']];

$azAuth = makeGuard($azProv, new FakeSession(), [
    'roles' => ['admin' => ['level' => 1], 'editor' => ['level' => 2, 'inherits' => ['user']], 'user' => ['level' => 3]],
    'acl_rules' => [
        ['target' => 'post.publish', 'subject' => 'user', 'effect' => 'deny', 'priority' => 10],
    ],
]);

$azAuth->login($azProv->retrieveById($adminId));
check('authz: roles() from provider', $azAuth->roles() === ['admin']);
check('authz: role() primary', $azAuth->role() === 'admin');
check('authz: hasRole / anyRole / allRoles', $azAuth->hasRole('admin') && $azAuth->anyRole(['x', 'admin']) && $azAuth->allRoles(['admin']));
check('authz: admin permission via role', $azAuth->hasPermission('user.manage'));
check('authz: admin inherits editor perms through hierarchy', $azAuth->hasPermission('post.edit'));
check('authz: level() reflects hierarchy', $azAuth->level() === 1);
check('authz: can() with context', $azAuth->can('system.config'));
check('authz: cannot() for missing perm', $azAuth->cannot('nonexistent.perm'));
check('authz: multi-rule hasAllPermissions', $azAuth->hasAllPermissions(['user.manage', 'post.edit']));
check('authz: multi-rule hasAnyPermission', $azAuth->hasAnyPermission(['nope', 'user.manage']));
check('authz: canActOn lower level (admin > user)', $azAuth->canActOn($plebId));
check('authz: cannot act on peer admin', !$azAuth->canActOn(['id' => 999, 'role' => 'admin']));

$azAuth->login($azProv->retrieveById($plebId));
check('authz: plain user lacks admin perms', !$azAuth->hasPermission('user.manage'));
check('authz: ACL deny overrides grant (editor publish denied for... no)', $azAuth->cannot('post.publish'));
// user role has post.create but NOT post.publish; give it via rolePerms then ACL-deny:
$azProv->rolePerms['user'][] = 'post.publish';
$azAuth->forgetAuthorizationCache();
check('authz: after cache flush grant exists but ACL deny still blocks', $azAuth->hasPermission('post.publish') && !$azAuth->allows('post.publish'));
check('authz: editor (no ACL match) keeps its own grant', (function () use ($azProv, $azAuth, $editorId) {
    $azAuth->login($azProv->retrieveById($editorId));
    $azAuth->forgetAuthorizationCache();
    return $azAuth->allows('post.edit');
})());
check('authz: editor acts on plain user', $azAuth->canActOn($plebId));
check('authz: editor cannot act on admin', !$azAuth->canActOn($adminId));

try { $azAuth->authorize('user.manage'); check('authz: authorize throws on denial', false); }
catch (AuthException) { check('authz: authorize throws on denial', true); }

// runtime ACL rules extend access beyond grants
$azAuth->addAclRule(['target' => 'reports.view', 'subject' => 'editor', 'effect' => 'allow']);
check('authz: runtime ACL rule grants beyond role perms', $azAuth->can('reports.view'));
$expl = $azAuth->explain('reports.view');
check('authz: guard explain() finds the rule', $expl['allowed'] === true && $expl['via']['target'] === 'reports.view');

// provider rules participate too
$azProv->rules[] = ['target' => 'temp.feature', 'subject' => 'editor', 'effect' => 'allow'];
$azAuth->forgetAuthorizationCache();
check('authz: provider-supplied ACL rule honored', $azAuth->can('temp.feature'));

// =====================================================================
// 12. Flat model fallback: role column on the record, no authz provider
// =====================================================================
$flatProv = new FullProvider();
$flatId = $flatProv->seed('f@f.com', 'pw', ['role' => 'sales, support', 'permissions' => 'invoice.view']);
$flatAuth = makeGuard($flatProv, new FakeSession(), ['roles' => ['sales' => 2, 'support' => 3]]);
$flatAuth->login($flatProv->retrieveById($flatId));
check('flat: role column parsed (comma list)', $flatAuth->roles() === ['sales', 'support']);
check('flat: primary role first', $flatAuth->role() === 'sales');
check('flat: direct permission column works', $flatAuth->hasPermission('invoice.view'));
check('flat: unknown permission denied', !$flatAuth->hasPermission('invoice.delete'));
check('flat: level from config shorthand', $flatAuth->level() === 2);

// =====================================================================
// 13. Facade passthrough of authorization methods
// =====================================================================
foreach (['roles','role','permissions','hasPermission','hasAllPermissions','hasAnyPermission','hasRole','anyRole','allRoles','allows','can','cannot','authorize','canActOn','level','addAclRule','setAclRules'] as $m) {
    check("Facade::$m defined", method_exists(Facade::class, $m));
}
check('AuthInterface declares allows()', in_array('allows', get_class_methods(\Kodhe\Framework\Auth\AuthInterface::class), true));

echo "\nRESULT: $pass passed, $fail failed\n";
exit($fail === 0 ? 0 : 1);
