<?php

declare(strict_types=1);

// Runtime verification harness for the Auth <-> Socialite integration:
// ONE unified login system where password logins (Auth::attempt) and
// social logins (SocialiteAuth) share the same guard, storage, session
// payload, events and authorization stack.
// Run: php auth/tests/socialite_integration_test.php   (exit 0 = all green)

error_reporting(E_ALL);

$authSrc = dirname(__DIR__) . '/src/';
$socSrc  = dirname(__DIR__, 2) . '/socialite/src/';

foreach ([
    $authSrc . 'AuthInterface.php',
    $authSrc . 'Contracts/UserProviderInterface.php',
    $authSrc . 'Contracts/RegisterableProviderInterface.php',
    $authSrc . 'Contracts/UpdatableProviderInterface.php',
    $authSrc . 'Contracts/AuthEventDispatcherInterface.php',
    $authSrc . 'RoleHierarchy.php',
    $authSrc . 'GroupTree.php',
    $authSrc . 'Acl.php',
    $authSrc . 'AuthEvents.php',
    $authSrc . 'AuthException.php',
    $authSrc . 'Auth.php',
    $socSrc . 'Contracts/HttpClientInterface.php',
    $socSrc . 'Contracts/StateStoreInterface.php',
    $socSrc . 'ProviderInterface.php',
    $socSrc . 'SocialiteException.php',
    $socSrc . 'InvalidStateException.php',
    $socSrc . 'RedirectResponse.php',
    $socSrc . 'SocialiteUser.php',
    $socSrc . 'Http/Request.php',
    $socSrc . 'Http/CurlClient.php',
    $socSrc . 'Session/ArrayStateStore.php',
    $socSrc . 'AbstractProvider.php',
    $socSrc . 'Two/AbstractProvider2.php',
    $socSrc . 'Two/GoogleProvider.php',
    $socSrc . 'Two/GithubProvider.php',
    $socSrc . 'Manager.php',
    $authSrc . 'SocialiteAuth.php',
] as $file) {
    require_once $file;
}

use Kodhe\Framework\Auth\Auth;
use Kodhe\Framework\Auth\AuthEvents;
use Kodhe\Framework\Auth\AuthException;
use Kodhe\Framework\Auth\Contracts\AuthEventDispatcherInterface;
use Kodhe\Framework\Auth\Contracts\RegisterableProviderInterface;
use Kodhe\Framework\Auth\Contracts\UpdatableProviderInterface;
use Kodhe\Framework\Auth\Contracts\UserProviderInterface;
use Kodhe\Framework\Auth\SocialiteAuth;
use Kodhe\Framework\Socialite\Contracts\HttpClientInterface;
use Kodhe\Framework\Socialite\Http\Request;
use Kodhe\Framework\Socialite\Manager;
use Kodhe\Framework\Socialite\Session\ArrayStateStore;
use Kodhe\Framework\Socialite\SocialiteUser;
use Kodhe\Framework\Socialite\Two\GoogleProvider;

// ---------------------------------------------------------------------
// Fakes
// ---------------------------------------------------------------------

/** In-memory user store shared by BOTH login modes. */
class ArrayUserProvider implements UserProviderInterface, RegisterableProviderInterface, UpdatableProviderInterface
{
    /** @var array<int,array> */
    public array $users = [];
    protected int $autoId = 0;

    public function seed(array $user): int
    {
        $id = ++$this->autoId;
        $user['id'] = $id;
        $this->users[$id] = $user;
        return $id;
    }

    public function retrieveByIdentifier(string $identifier, string $value, array $extra = []): ?array
    {
        foreach ($this->users as $user) {
            if (!array_key_exists($identifier, $user)) {
                continue;
            }
            $cell = $user[$identifier];
            if (is_array($cell)) {
                // List-valued column (e.g. social_accounts): exact member match.
                if (in_array($value, array_map('strval', $cell), true)) {
                    return $user;
                }
                continue;
            }
            if ((string) $cell === $value) {
                return $user;
            }
        }
        return null;
    }

    public function retrieveById(int|string $id): ?array
    {
        return $this->users[(int) $id] ?? null;
    }

    public function updateRememberToken(int|string $id, ?string $token): void
    {
        $this->users[(int) $id]['remember_token'] = $token;
    }

    public function hasIdentifier(string $identifier, string $value): bool
    {
        return $this->retrieveByIdentifier($identifier, $value) !== null;
    }

    public function createUser(array $attributes, string $passwordHash): int|string
    {
        $id = ++$this->autoId;
        $this->users[$id] = array_merge($attributes, [
            'id'       => $id,
            'password' => $passwordHash,
        ]);
        return $id;
    }

    public function updateUser(int|string $id, array $columns): void
    {
        $this->users[(int) $id] = array_merge($this->users[(int) $id] ?? [], $columns);
    }
}

/** Minimal session double with the CI-style API the guard expects. */
class FakeSession
{
    public array $data = [];

    public function set_userdata(array $payload): void
    {
        foreach ($payload as $k => $v) {
            $this->data[$k] = $v;
        }
    }

    public function userdata(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function unset_userdata(array|string $keys): void
    {
        foreach ((array) $keys as $k) {
            unset($this->data[$k]);
        }
    }
}

class RecordingDispatcher implements AuthEventDispatcherInterface
{
    /** @var array<int,array{event:string,payload:array}> */
    public array $events = [];

    public function dispatch(string $event, array $payload = []): void
    {
        $this->events[] = ['event' => $event, 'payload' => $payload];
    }

    public function names(): array
    {
        return array_column($this->events, 'event');
    }
}

/** Scripted OAuth endpoint responses for the fake Google server. */
class FakeHttp implements HttpClientInterface
{
    public function __construct(public array $responses = []) {}

    public function request(string $method, string $url, array $options = []): array
    {
        return $this->responseFor($url);
    }

    public function get(string $url, array $options = []): array
    {
        return $this->responseFor($url);
    }

    public function post(string $url, array $options = []): array
    {
        return $this->responseFor($url);
    }

    protected function responseFor(string $url): array
    {
        foreach ($this->responses as $needle => $response) {
            if (str_contains($url, $needle)) {
                return array_merge(['status' => 200, 'text' => '', 'json' => null], $response);
            }
        }
        throw new RuntimeException('Unexpected HTTP call: ' . $url);
    }
}

/** State store that keeps values across put()/pull() (models a real session). */
class PersistentArrayStateStore extends ArrayStateStore
{
    public function pull(string $key): ?array
    {
        return $this->data[$key] ?? null;
    }
}

/** Google driver wired to fakes — no network, no real PHP session needed. */
function makeBridge(?ArrayUserProvider $store, FakeSession $session, RecordingDispatcher $events, array $overrides = []): array
{
    $guard = new Auth(['max_attempts' => 0], $store ?? new ArrayUserProvider());
    $guard->setEventDispatcher($events);
    // Attach our fake session to the guard (resolveSession() returns null
    // under CLI without a framework bootstrap).
    $prop = new ReflectionProperty($guard, 'session');
    $prop->setAccessible(true);
    $prop->setValue($guard, $session);

    $http = new FakeHttp([
        'oauth2.googleapis.com/token' => [
            'json' => ['access_token' => 'ya29.fake-token', 'expires_in' => 3600, 'refresh_token' => 'rt-1'],
        ],
        'openidconnect.googleapis.com' => [
            'json' => [
                'sub'            => 'g-1234567890',
                'email'          => 'budi@example.com',
                'email_verified' => true,
                'name'           => 'Budi Santoso',
                'picture'        => 'https://example.com/budi.png',
            ],
        ],
    ]);

    // Simulate the REAL two-step flow: (1) start redirects and captures the
    // generated state, (2) the provider callback returns that same state.
    $startRequest = new Request([], 'https://app.test');
    $store2       = new PersistentArrayStateStore();

    $startProvider = new GoogleProvider(
        'google',
        ['client_id' => 'cid', 'client_secret' => 'secret', 'redirect' => 'https://app.test/callback/google'],
        $http,
        $startRequest,
        $store2,
    );

    $consentUrl = $startProvider->authorizationUrl();
    parse_str((string) parse_url($consentUrl, PHP_URL_QUERY), $q);
    $state = (string) ($q['state'] ?? '');

    $provider = new GoogleProvider(
        'google',
        ['client_id' => 'cid', 'client_secret' => 'secret', 'redirect' => 'https://app.test/callback/google'],
        $http,
        new Request(['code' => 'the-code', 'state' => $state]),
        $store2,
    );

    $manager = new Manager(['providers' => ['google' => ['client_id' => 'cid', 'client_secret' => 'secret']]]);
    $manager->extend('google', fn () => $provider);

    $bridge = new SocialiteAuth(
        array_merge(['throw_on_error' => true, 'guard' => $guard, 'manager' => fn () => $manager], $overrides),
        $guard,
    );

    return [$bridge, $guard];
}

/** Dedupe helper: config + custom-registered drivers may list a name twice. */
function array_values_unique(array $a): array
{
    return array_values(array_unique($a));
}

// ---------------------------------------------------------------------
// Tiny assertion framework
// ---------------------------------------------------------------------

$failures = 0;
$checks   = 0;

function ok(bool $cond, string $label): void
{
    global $failures, $checks;
    $checks++;
    if ($cond) {
        echo "  [OK] {$label}\n";
    } else {
        $failures++;
        echo "  [FAIL] {$label}\n";
    }
}

function eq(mixed $actual, mixed $expected, string $label): void
{
    ok($actual === $expected, $label . ' (got: ' . var_export($actual, true) . ')');
}

// ---------------------------------------------------------------------
// Scenario A: password account exists -> Google login LINKS to it
// ---------------------------------------------------------------------

echo "A. Password account + Google callback links to the SAME account\n";

$store   = new ArrayUserProvider();
$session = new FakeSession();
$events  = new RecordingDispatcher();

$userId = $store->seed([
    'email'    => 'budi@example.com',
    'name'     => 'Budi',
    'password' => Auth::hashPassword('rahasia123'),
    'role'     => 'editor',
]);

[$bridge, $guard] = makeBridge($store, $session, $events);

// A1: classic password login still works through the unified guard.
ok($guard->attempt('budi@example.com', 'rahasia123'), 'password attempt() succeeds');
eq($guard->id(), $userId, 'password login resolves to seeded user id');
$guard->logout(false);
ok(!$guard->check(), 'logout clears the guard');

// A2: social login on the callback finds the e-mail match and links.
$user = $bridge->login('google');
ok($user !== null, 'social login returns a local user record');
eq((int) $user['id'], $userId, 'social login linked to the EXISTING account (no duplicate)');
ok(in_array('google:g-1234567890', (array) $store->users[$userId]['social_accounts'], true), 'identity google:g-1234567890 persisted');
eq($store->users[$userId]['google_id'], 'g-1234567890', 'google_id column set');
eq((int) $guard->id(), $userId, 'same guard/session is logged in after social login');
eq($guard->id('email'), 'budi@example.com', 'guard exposes the linked user email');
ok(in_array(AuthEvents::SOCIAL_LOGIN, $events->names(), true), 'SOCIAL_LOGIN event fired');
ok(in_array(AuthEvents::USER_LOGGED_IN, $events->names(), true), 'shared USER_LOGGED_IN event also fired');
$socialEvent = null;
foreach ($events->events as $e) {
    if ($e['event'] === AuthEvents::SOCIAL_LOGIN) {
        $socialEvent = $e['payload'];
    }
}
eq($socialEvent['provider'] ?? null, 'google', 'event payload carries provider');
eq($socialEvent['linked'] ?? null, true, 'event payload flags a link (not a create)');
eq($socialEvent['created'] ?? null, false, 'event payload: not newly created');

// Roles/permissions flow through unchanged: social login == password login.
eq($guard->role(), 'editor', 'roles available after social login (unified authorization)');

// A3: second social login matches by identity ref, not by e-mail again.
$events2 = new RecordingDispatcher();
[$bridge2] = makeBridge($store, $session, $events2);
$user2 = $bridge2->login('google');
eq((int) $user2['id'], $userId, 'repeat social login matches the stored identity ref');
$p = null;
foreach ($events2->events as $e) {
    if ($e['event'] === AuthEvents::SOCIAL_LOGIN) {
        $p = $e['payload'];
    }
}
eq($p['linked'] ?? null, false, 'repeat login neither creates nor links');

// ---------------------------------------------------------------------
// Scenario B: first-time social login CREATES an account
// ---------------------------------------------------------------------

echo "\nB. First-time Google login registers a new account automatically\n";

$store   = new ArrayUserProvider();
$session = new FakeSession();
$events  = new RecordingDispatcher();

[$bridge, $guard] = makeBridge($store, $session, $events);

$user = $bridge->login('google');
ok($user !== null, 'new user created from Google profile');
eq($user['email'], 'budi@example.com', 'email copied from provider');
eq($user['name'], 'Budi Santoso', 'name copied from provider');
eq($user['avatar'], 'https://example.com/budi.png', 'avatar copied from provider');
ok(!empty($user['email_verified_at']), 'trusted provider auto-marks email verified');
ok(str_starts_with((string) $user['password'], '$2y$'), 'random bcrypt password stored');
ok(!isset($guard->user()['password']), 'password hash absent from the session copy');
eq(count($store->users), 1, 'exactly one account exists');
$last = end($events->events);
eq($last['payload']['created'] ?? null, true, 'event payload flags creation');

// B2: password login now ALSO works for this socially-created account
// (unified system: users can switch channels freely).
$guard->logout(false);
$plain = 'my-new-password';
$store->updateUser(1, ['password' => Auth::hashPassword($plain)]);
ok($guard->attempt('budi@example.com', $plain), 'social user can log in with a password afterwards');
eq((int) $guard->id(), 1, 'password channel lands on the same social-created account');

// ---------------------------------------------------------------------
// Scenario C: configuration switches
// ---------------------------------------------------------------------

echo "\nC. Config switches behave\n";

// C1: register_new_users=false + no match -> failure surfaced.
[$bridge] = makeBridge(null, new FakeSession(), new RecordingDispatcher(), ['register_new_users' => false, 'link_by_email' => false]);
try {
    $bridge->login('google');
    ok(false, 'should have thrown when no account may be used');
} catch (AuthException $e) {
    ok(str_contains($e->getMessage(), 'No local account'), 'clear error when registration disabled');
}

// C2: non-throwing mode returns null and fires FAILED.
$events2 = new RecordingDispatcher();
[$bridge2] = makeBridge(null, new FakeSession(), $events2, [
    'throw_on_error' => false, 'register_new_users' => false, 'link_by_email' => false,
]);
eq($bridge2->login('google'), null, 'non-throwing mode returns null on failure');
ok(in_array(AuthEvents::FAILED, $events2->names(), true), 'FAILED event fired for social failures');

// C3: require_verified_email_for_link allows verified profiles.
$store3 = new ArrayUserProvider();
$store3->seed(['email' => 'budi@example.com', 'password' => Auth::hashPassword('x'), 'role' => 'member']);
[$bridge3] = makeBridge($store3, new FakeSession(), new RecordingDispatcher(), [
    'require_verified_email_for_link' => true,
]);
$user3 = $bridge3->login('google');
ok($user3 !== null, 'verified email allows linking when hardening enabled');

// C4: drivers stamp __provider so the bridge always knows the source.
$u = new SocialiteUser();
$u->setId('x1')->setEmail('x@y.z')->setExtra(['__provider' => 'github']);
eq($u->getExtra()['__provider'] ?? null, 'github', 'SocialiteUser carries the provider name');

// ---------------------------------------------------------------------
// Scenario D: redirect plumbing
// ---------------------------------------------------------------------

echo "\nD. Redirect plumbing\n";

[$bridge] = makeBridge(new ArrayUserProvider(), new FakeSession(), new RecordingDispatcher(), ['redirect_after_login' => '/home']);
eq($bridge->successUrl(), '/home', 'successUrl falls back to configured redirect');
eq($bridge->loginUrl('Google'), '/auth/socialite/google', 'loginUrl normalizes provider case');
ok($bridge->isConfigured('google'), 'isConfigured sees manager providers');
eq(array_values_unique($bridge->enabledProviders()), ['google'], 'enabledProviders lists configured drivers');

$resp = $bridge->redirect('google');
$url  = $resp->getTargetUrl();
ok(str_starts_with($url, 'https://accounts.google.com/o/oauth2/v2/auth?'), 'redirect targets Google consent page');
ok(str_contains($url, 'state='), 'CSRF state parameter present in consent URL');
ok(str_contains($url, 'client_id=cid'), 'client_id present in consent URL');

// complete() returns the destination URL on success.
$store4 = new ArrayUserProvider();
$store4->seed(['email' => 'budi@example.com', 'password' => Auth::hashPassword('x')]);
[$bridge4] = makeBridge($store4, new FakeSession(), new RecordingDispatcher(), ['redirect_after_login' => '/done']);
eq($bridge4->complete('google'), '/done', 'complete() logs in and returns the redirect target');

// ---------------------------------------------------------------------
// Scenario E: "Connect to my profile" — linking flow through the bridge
// ---------------------------------------------------------------------

echo "\nE. Connect (link) flow for a logged-in user\n";

/**
 * Build a bridge whose START provider (redirect/connect side) and CALLBACK
 * provider share one state store, so withStateData() payloads round-trip
 * exactly like they do across real browser redirects.
 */
function makeConnectBridge(ArrayUserProvider $store, FakeSession $session, RecordingDispatcher $events, array $overrides = []): array
{
    $guard = new Auth(['max_attempts' => 0], $store);
    $guard->setEventDispatcher($events);
    $prop = new ReflectionProperty($guard, 'session');
    $prop->setAccessible(true);
    $prop->setValue($guard, $session);

    $http = new FakeHttp([
        'oauth2.googleapis.com/token' => [
            'json' => ['access_token' => 'ya29.fake-token', 'expires_in' => 3600],
        ],
        'openidconnect.googleapis.com' => [
            'json' => [
                'sub'   => 'g-999',
                'email' => 'budi@example.com',   // same e-mail as another account!
                'email_verified' => true,
                'name'  => 'Budi Santoso',
            ],
        ],
    ]);

    $sharedStore = new PersistentArrayStateStore();
    $providerCfg = ['client_id' => 'cid', 'client_secret' => 'secret', 'redirect' => 'https://app.test/callback/google'];

    // ONE shared Manager instance: extend('google') below must register on
    // the very manager the bridge resolves drivers from.
    $manager = new Manager();

    $bridge = new SocialiteAuth(
        array_merge([
            'throw_on_error' => true,
            'guard'          => $guard,
            'manager'        => $manager,
        ], $overrides),
        $guard,
    );
    // The bridge reads/writes the connect nonce via session(); point it at
    // the same FakeSession the guard uses.
    $bridge->setSession($session);

    // One driver handle that behaves like the real browser round-trip:
    // connect()/redirect() runs on the START side (mints state, embeds the
    // link nonce); the next user() call is simulated as the provider
    // CALLBACK — a fresh instance replaying ?code=&state= against the SAME
    // state store, exactly like scenario A does for plain logins.
    $startDriver = new class('google', $providerCfg, $http, new Request([], 'https://app.test'), $sharedStore) extends GoogleProvider {
        public string $consentUrl = '';

        public function authorizationUrl(): string
        {
            // Fresh page-load semantics: mint a NEW state payload per
            // connect()/redirect() call (a real browser request starts
            // with empty per-request stateData).
            $this->stateData = [];
            $this->consentUrl = parent::authorizationUrl();
            return $this->consentUrl;
        }

        public function rawConfig(): array { return $this->config; }
        public function stateStoreRef(): \Kodhe\Framework\Socialite\Contracts\StateStoreInterface { return $this->stateStore; }
    };

    $driver = new class('google', $providerCfg, $http, new Request([], 'https://app.test'), $sharedStore) extends GoogleProvider {
        /** The start-side driver whose consent URL we replay against. */
        public mixed $start = null;

        // Bridge hook: after the callback validates the state, expose the
        // START instance's payload (it carries the link nonce minted by
        // connect()) — that's what consumeLinkNonce() reads.
        public function stateData(): ?array
        {
            return $this->start->stateData();
        }

        // Start side: forward redirect()/authorizationUrl() to the start
        // instance so connect() mints its state there.
        public function redirect(array $scopes = [])
        {
            return $this->start->redirect($scopes);
        }

        public function authorizationUrl(): string
        {
            return $this->start->authorizationUrl();
        }

        // Callback side: rebuild the request with the code+state the
        // provider would have sent the browser back with.
        public function user(): SocialiteUser
        {
            parse_str((string) parse_url($this->start->consentUrl, PHP_URL_QUERY), $q);
            $this->request = new Request(
                ['code' => 'fake-auth-code', 'state' => (string) ($q['state'] ?? '')],
                'https://app.test/callback/google',
            );

            return parent::user();
        }
    };
    $driver->start = $startDriver;

    $bridge->socialite()->extend('google', fn () => $driver);

    return [$bridge, $guard, $driver];
}

// Accounts: budi owns nothing yet; carol ALREADY owns google:g-999.
$storeE = new ArrayUserProvider();
$budiId  = $storeE->seed(['email' => 'budi@example.com',  'password' => Auth::hashPassword('pw')]);
$carolId = $storeE->seed(['email' => 'carol@example.com', 'password' => Auth::hashPassword('pw'), 'social_accounts' => ['google:g-999']]);

$sessionE = new FakeSession();
$eventsE  = new RecordingDispatcher();
[$bridgeE, $guardE, $driverE] = makeConnectBridge($storeE, $sessionE, $eventsE);

// Guest: connect() must degrade to a plain login redirect (no nonce).
$guestResp = $bridgeE->connect('google');
ok(str_contains($guestResp->getTargetUrl(), 'accounts.google.com'), 'guest connect() still redirects to Google');
ok($sessionE->userdata('socialite_connect_nonce') === null, 'guest gets no connect nonce in session');

// Log Budi in with his PASSWORD (unified guard: same session either way).
$guardE->login($storeE->users[$budiId]);
ok($guardE->check(), 'Budi is logged in via the password path');

// Step 1: Budi clicks "Connect Google" -> nonce stashed, consent URL minted.
$connResp = $bridgeE->connect('google');
$nonce    = $sessionE->userdata('socialite_connect_nonce');
ok(is_string($nonce) && strlen($nonce) === 32, 'connect() mints a 32-hex nonce in the session');
ok(str_contains($connResp->getTargetUrl(), 'state='), 'consent URL carries the CSRF state');

// Step 2: the callback arrives. Because Budi is logged in AND the nonce
// matches, the identity g-999 must NOT hop to Carol's account even though
// Carol already owns it — instead the flow refuses with a clear error.
$thrown = null;
try {
    $bridgeE->login('google');
} catch (\Throwable $e) {
    $thrown = $e->getMessage();
}
ok(is_string($thrown) && str_contains(strtolower($thrown), 'different user'),
    'connect() refuses to hijack an identity owned by another account');
eq((int) $guardE->id(), $budiId, 'Budi stays logged in after the refused connect');
ok(in_array(AuthEvents::FAILED, $eventsE->names(), true), 'a FAILED event was fired for the refused connect');

// Fresh scenario: nobody owns g-999 yet -> connect links it to the
// logged-in account (Dani) even though the e-mail matches Budi's.
$storeG = new ArrayUserProvider();
$daniId = $storeG->seed(['email' => 'dani@example.com', 'password' => Auth::hashPassword('pw')]);
$sessionG = new FakeSession();
$eventsG  = new RecordingDispatcher();
[$bridgeG, $guardG, $driverG] = makeConnectBridge($storeG, $sessionG, $eventsG);
$guardG->login($storeG->users[$daniId]);
$bridgeG->connect('google');           // stash nonce + mint state
$userG = $bridgeG->login('google');    // callback links g-999 to Dani

eq((int) ($userG['id'] ?? -1), $daniId, 'connect() links the identity to the LOGGED-IN account');
ok(in_array('google:g-999', (function (mixed $v): array { return is_array($v) ? $v : array_filter(array_map('trim', explode(',', (string) $v))); })($userG['social_accounts'] ?? ''), true),
    'social_accounts now contains google:g-999');
eq($sessionG->userdata('socialite_connect_nonce'), null, 'connect nonce is consumed exactly once');
ok(in_array(AuthEvents::SOCIAL_LINK, $eventsG->names(), true), 'SOCIAL_LINK event fired on connect');
eq(count(array_filter($storeG->users, fn ($u) => ($u['email'] ?? '') === 'budi@example.com')), 0,
    'no duplicate account created by e-mail during a connect flow');

// Plain login (no nonce) with the SAME identity now finds Dani by ref.
$eventsH = new RecordingDispatcher();
// Plain login WITHOUT connect(): redirect() first (as a real controller
// would), then the callback resolves the identity by linked ref.
[$bridgeH, $guardH, $driverH] = makeConnectBridge($storeG, new FakeSession(), $eventsH);
$bridgeH->redirect('google');          // "click Google" on the login page
$userH = $bridgeH->login('google');
eq((int) ($userH['id'] ?? -1), $daniId, 'subsequent plain social login resolves the linked account');

// ---------------------------------------------------------------------
// Scenario F: handle() — one entry point for both social endpoints,
// plus the ready-made framework-agnostic route table.
// ---------------------------------------------------------------------

// F1: start phase returns a RedirectResponse toward the provider consent
// page and remembers the intended URL.
[$bridgeF, $guardF] = makeConnectBridge(new ArrayUserProvider(), new FakeSession(), new RecordingDispatcher(), [
    'register_new_users' => true,
]);
$respF = $bridgeF->handle('Google', 'start', ['intended' => '/checkout']);
ok($respF instanceof \Kodhe\Framework\Socialite\RedirectResponse,
    'handle(provider, "start") returns a RedirectResponse');
ok(str_contains($respF->getTargetUrl(), 'accounts.google.com'),
    'start phase redirects to the Google consent page');
eq($bridgeF->intendedUrl(false), '/checkout', 'handle() stashed the intended URL');

// F2: callback phase logs the user in and redirects to the intended URL.
$userF = null;
try {
    $cbF = $bridgeF->handle('google', 'callback');
    $userF = $guardF->user();
} catch (\Throwable $e) {
    ok(false, 'callback phase threw: ' . $e->getMessage());
}
ok(is_array($userF) && ($userF['email'] ?? '') === 'budi@example.com',
    'handle(callback) completed the login through the shared guard');
eq($cbF->getTargetUrl(), '/checkout', 'callback phase honours the remembered intended URL');
ok($guardF->check(), 'session is authenticated after handle(callback)');

// F3: unknown/unconfigured provider -> error redirect (never throws when
// throw_on_error is off). The bridge has NO google driver registered and
// no credentials, so handle() must bounce to config['redirect_on_error'].
$bridgeG2 = new SocialiteAuth([
    'throw_on_error'     => false,
    'guard'              => $guardF,
    'redirect_on_error'  => '/login?error=social',
]);
$errF = $bridgeG2->handle('google', 'start');
ok($errF instanceof \Kodhe\Framework\Socialite\RedirectResponse,
    'unconfigured provider start returns a RedirectResponse, not an exception');
eq($errF->getTargetUrl(), '/login?error=social', 'unconfigured provider redirects to redirect_on_error');

// F4: the bundled route table exposes the three endpoints and its handlers
// delegate to handle() correctly.
$routeFile = dirname(__DIR__) . '/routes/socialite.php';
ok(is_file($routeFile), 'auth/routes/socialite.php exists');
$routes = require $routeFile;
eq(count($routes), 3, 'route table declares start / connect / callback');
$paths = array_map(static fn (array $r): string => $r['path'], $routes);
ok(in_array('/auth/socialite/{provider}', $paths, true), 'table has the start route');
ok(in_array('/auth/socialite/{provider}/connect', $paths, true), 'table has the connect route');
ok(in_array('/auth/socialite/{provider}/callback', $paths, true), 'table has the callback route');
ok($routes[0]['handler'] instanceof Closure && $routes[2]['handler'] instanceof Closure,
    'route handlers are callables');

// ---------------------------------------------------------------------
// Scenario G: token vault — social login persists OAuth tokens, and
// accessTokenFor() renews them transparently once expired.
// ---------------------------------------------------------------------

echo "G. Token storage + automatic refresh via refreshAccessToken()\n";

$storeG2   = new ArrayUserProvider();
$sessionG2 = new FakeSession();
$eventsG2  = new RecordingDispatcher();

[$bridgeG3, $guardG2, $driverG] = makeConnectBridge($storeG2, $sessionG2, $eventsG2);

$driverG->redirect();                  // start the flow (mints the state)
$userG2 = $bridgeG3->login('google');
ok(is_array($userG2), 'social login succeeded in the token-vault scenario');

// G1: tokens were persisted automatically by completeLogin().
$saved = $storeG2->users[(int) $userG2['id']]['social_tokens']['google'] ?? null;
ok(is_array($saved), 'social login stored tokens under the social_tokens column');
eq($saved['access_token'] ?? null, 'ya29.fake-token', 'stored access token matches the callback payload');
eq($saved['refresh_token'] ?? null, null, 'no refresh token present yet (provider did not issue one)');
ok(($saved['expires_at'] ?? 0) > time() + 3500, 'expires_at computed from expires_in');

// G2: while unexpired, accessTokenFor() returns the stored token untouched.
eq($bridgeG3->accessTokenFor('google'), 'ya29.fake-token', 'unexpired stored token is returned as-is');

// G3: simulate expiry + a stored refresh token -> renewal through the
// driver's refresh endpoint, with the NEW pair written back to storage.
$uidG2 = (int) $userG2['id'];
$storeG2->users[$uidG2]['social_tokens']['google']['expires_at']   = time() - 10;
$storeG2->users[$uidG2]['social_tokens']['google']['refresh_token'] = 'rt-1';
$guardG2->setUser($storeG2->users[$uidG2]);

$httpProp = new ReflectionProperty(\Kodhe\Framework\Socialite\AbstractProvider::class, 'http');
$httpProp->setAccessible(true);
$reflected = new class($httpProp->getValue($driverG)) implements \Kodhe\Framework\Socialite\Contracts\HttpClientInterface {
    public function __construct(public $inner) {}
    public function request(string $m, string $u, array $o = []): array { return $this->route($u, $o); }
    public function get(string $u, array $o = []): array { return $this->route($u, $o); }
    public function post(string $u, array $o = []): array { return $this->route($u, $o); }
    protected function route(string $u, array $o): array
    {
        if (($o['form']['grant_type'] ?? null) === 'refresh_token') {
            return ['status' => 200, 'text' => '', 'json' => [
                'access_token' => 'ya29.renewed-token', 'expires_in' => 7200,
            ]];
        }
        return $this->inner->post($u, $o);
    }
};
$httpProp->setValue($driverG, $reflected);

$freshToken = $bridgeG3->accessTokenFor('google');
eq($freshToken, 'ya29.renewed-token', 'expired token renewed via refreshAccessToken()');
$after = $storeG2->users[$uidG2]['social_tokens']['google'];
eq($after['access_token'], 'ya29.renewed-token', 'renewed access token persisted to storage');
eq($after['refresh_token'], 'rt-1', 'previous refresh token kept when provider does not rotate it');
ok($after['expires_at'] > time() + 7100, 'new expiry recorded after refresh');

// G4: disconnect clears the stored tokens for that provider. The session
// copy never carries the password column (the guard strips it), so check
// against the stored record to decide whether removal is safe.
$updated = $bridgeG3->disconnect('google', $storeG2->users[$uidG2]);
ok(!isset($updated['social_tokens']['google']), 'disconnect removes the stored google tokens');
eq($bridgeG3->accessTokenFor('google'), null, 'no usable token after disconnect');

// ---------------------------------------------------------------------

echo "\n{$checks} checks, {$failures} failure(s)\n";
exit($failures === 0 ? 0 : 1);
