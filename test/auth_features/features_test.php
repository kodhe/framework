<?php

declare(strict_types=1);

// Runtime verification harness for the new auth features (registration,
// password change/reset, email verification, throttling, events).
// Run: php test/auth_features/features_test.php   (exit 0 = all green)

error_reporting(E_ALL);

$base = dirname(__DIR__, 2) . '/auth/src/';
require_once $base . 'AuthInterface.php';
require_once $base . 'Contracts/UserProviderInterface.php';
require_once $base . 'Contracts/RegisterableProviderInterface.php';
require_once $base . 'Contracts/UpdatableProviderInterface.php';
require_once $base . 'Contracts/AuthEventDispatcherInterface.php';
require_once $base . 'AuthEvents.php';
require_once $base . 'AuthException.php';
require_once $base . 'Auth.php';
require_once $base . 'Support/Facade.php';

use Kodhe\Framework\Auth\Auth;
use Kodhe\Framework\Auth\AuthEvents;
use Kodhe\Framework\Auth\AuthException;
use Kodhe\Framework\Auth\Contracts\AuthEventDispatcherInterface;
use Kodhe\Framework\Auth\Contracts\RegisterableProviderInterface;
use Kodhe\Framework\Auth\Contracts\UpdatableProviderInterface;
use Kodhe\Framework\Auth\Contracts\UserProviderInterface;
use Kodhe\Framework\Auth\Facade;

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
function makeGuard(FullProvider $provider, FakeSession $session, array $cfg = []): Auth
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

echo "\nRESULT: $pass passed, $fail failed\n";
exit($fail === 0 ? 0 : 1);
