<?php

declare(strict_types=1);

namespace Kodhe\Framework\Auth;

use Kodhe\Framework\Auth\Contracts\RegisterableProviderInterface;
use Kodhe\Framework\Auth\Contracts\UpdatableProviderInterface;
use Kodhe\Framework\Auth\Contracts\UserProviderInterface;
use Kodhe\Framework\Socialite\Manager;
use Kodhe\Framework\Socialite\RedirectResponse;
use Kodhe\Framework\Socialite\SocialiteUser;

/**
 * Bridge between the Auth guard and the native Socialite component so the
 * application has ONE unified login system instead of two parallel modes:
 *
 *   - password login  ->  auth()->attempt($email, $password)
 *   - social login    ->  social_auth()->redirect('google')
 *                          social_auth()->login('google')   (on the callback)
 *
 * Both flows end in exactly the same session payload, events, remember-me
 * handling and authorization stack (roles/permissions/ACL), because every
 * social login is routed through Auth::login() / Auth::register().
 *
 * The bridge performs the classic "find or create by provider identity"
 * strategy:
 *
 *   1. Look up a local user whose social-account column list contains
 *      "<provider>:<provider-id>"  (e.g. "google:10934821").
 *   2. If none matches, look up by e-mail (when enabled) and LINK the
 *      social identity to that existing account — after an optional
 *      email-verified check, so nobody can take over accounts by
 *      registering a throwaway social profile with someone else's e-mail.
 *   3. Otherwise REGISTER a new account with a random password (the user
 *      can set one later via the normal password-reset flow).
 *
 * Storage requirements for the configured UserProviderInterface:
 *   - RegisterableProviderInterface (to create accounts) — required.
 *   - UpdatableProviderInterface    (to link identities) — optional; when
 *     missing, linking is skipped and only fresh registrations work.
 *
 * @see \Kodhe\Framework\Socialite\Manager for the underlying OAuth client.
 */
class SocialiteAuth
{
    /** @var array<string,mixed> Resolved configuration (merged with defaults). */
    protected array $config;

    /** Per-provider OAuth token records saved by storeTokens(). */
    protected const TOKENS_COLUMN = 'social_tokens';

    protected AuthInterface $auth;

    /** @var array<string,mixed> Raw user-supplied config (before merging). */
    protected array $explicit;

    /** @var callable():Manager Lazy factory for the Socialite manager. */
    protected $managerFactory;

    /** Session override for standalone/test use (null = auto-detect). */
    protected ?object $sessionOverride = null;

    /**
     * Inject a session double/object to use instead of the auto-detected
     * one (framework Session, CI get_instance()->session, ...).
     */
    public function setSession(?object $session): static
    {
        $this->sessionOverride = $session;

        return $this;
    }

    /**
     * @param array $config Keys are documented in defaultConfig(); merge
     *                      semantics apply (partial arrays override defaults).
     */
    public function __construct(?array $config = null, ?AuthInterface $auth = null)
    {
        $this->explicit        = $config ?? [];
        // File config (application/config/socialite_auth.php or the bundled
        // template) is merged first, explicit constructor options win.
        $this->config          = self::mergeConfig(
            self::mergeConfig(self::defaultConfig(), static::readConfigFile()),
            $this->explicit
        );
        $guard               = $auth ?? ($this->config['guard'] ?? null);
        $this->auth            = $guard instanceof AuthInterface ? $guard : new Auth();

        // Normalise the manager config here too — a raw Manager instance is
        // valid input, but socialite() invokes the value as a factory.
        $managerConfig         = $this->config['manager'];
        $this->managerFactory  = match (true) {
            $managerConfig instanceof Manager => static fn (): Manager => $managerConfig,
            default                           => $managerConfig,
        };
    }

    /**
     * Sensible defaults; override via constructor or application/config/socialite_auth.php.
     *
     * @return array<string,mixed>
     */
    public static function defaultConfig(): array
    {
        return [
            // Column on the user record holding linked social identities,
            // as an array or comma-separated list of "<provider>:<id>" refs.
            'social_accounts_column' => 'social_accounts',

            // Extra columns copied from the social profile onto new accounts.
            'name_column'   => 'name',
            'email_column'  => 'email',
            'avatar_column' => 'avatar',

            // Reuse the guard's identifier column (usually "email") for lookup.
            'identifier_column' => null,

            // Attach a "<provider>_id" key (and friends) to new records.
            'set_provider_id' => true,

            // Automatically persist the OAuth tokens issued on each social
            // login into the "social_tokens" column so apps can call
            // provider APIs later and renew via accessTokenFor().
            'store_tokens' => true,

            // Auto-create accounts for first-time social logins.
            'register_new_users' => true,

            // Link a social identity to an existing account with the same
            // e-mail instead of creating a duplicate.
            'link_by_email' => true,

            // Require a verified e-mail before linking by e-mail is allowed
            // (account-takeover protection). Needs the guard's verify_column.
            'require_verified_email_for_link' => false,

            // Mark e-mails coming from trusted providers (Google, GitHub) as
            // verified automatically on registration.
            'auto_verify_trusted' => true,

            // Providers whose e-mail is considered trustworthy.
            'trusted_providers' => ['google', 'github', 'facebook', 'gitlab', 'linkedin'],

            // Fixed redirect target after a successful social login, or null
            // to honour an "intended" URL stashed in the session beforehand
            // (see rememberIntended()/intendedUrl()).
            'redirect_after_login' => '/dashboard',

            // Throw AuthException instead of redirecting when login fails.
            'throw_on_error' => false,

            // Where to send the browser when something goes wrong.
            'redirect_on_error' => '/login',

            // Route/path that starts the social flow for a given provider.
            'login_route' => '/auth/socialite',

            // Session slot for the post-login intended URL.
            'intended_key' => 'socialite_intended',

            // Session slot holding the "connect to my profile" nonce minted
            // by connect() and consumed on the callback by login().
            'connect_key' => 'socialite_connect_nonce',

            // Custom state store for CSRF "state" tokens (null = $_SESSION).
            'state_store' => null,

            // Explicit Manager instance, or a factory closure fn(): Manager.
            // Pass null + config_path to load config from a file instead.
            'manager'     => null,
            'config_path' => null,

            // The Auth guard to log into (null = fresh Auth with app config).
            'guard' => null,
        ];
    }

    /** Deep-ish merge: nested arrays merge by key, everything else overrides. */
    protected static function mergeConfig(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = self::mergeConfig($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }

    /**
     * Load config from common locations: explicit 'socialite_auth.php' next
     * to the socialite config, APPPATH/config, or helper-provided paths.
     *
     * @return array<string,mixed>
     */
    public static function readConfigFile(): array
    {
        $candidates = [];

        if (defined('APPPATH')) {
            $candidates[] = APPPATH . 'config/socialite_auth.php';
        }
        if (function_exists('config_path')) {
            $candidates[] = config_path('socialite_auth.php');
        }
        if (function_exists('app_path')) {
            $candidates[] = app_path('config/socialite_auth.php');
        }
        $candidates[] = dirname(__DIR__, 2) . '/socialite/config/socialite_auth.php';

        foreach ($candidates as $path) {
            if (is_file($path)) {
                $cfg = require $path;
                if (is_array($cfg)) {
                    return $cfg;
                }
            }
        }

        return [];
    }

    /* ------------------------------------------------------------------
     | Wiring
     * ------------------------------------------------------------------ */

    /**
     * Replace the Socialite manager (or inject a factory closure).
     */
    public function setManager(Manager|callable|null $manager): static
    {
        $this->managerFactory = match (true) {
            $manager instanceof Manager => static fn (): Manager => $manager,
            is_callable($manager)      => $manager,
            default                    => null,
        };

        return $this;
    }

    /**
     * The underlying Socialite Manager, built lazily from config.
     */
    public function socialite(): Manager
    {
        if (!class_exists(Manager::class)) {
            throw new \RuntimeException(
                'SocialiteAuth requires the kodhe/socialite component (autoload path: socialite/src).'
            );
        }

        if ($this->managerFactory === null) {
            $manager = new Manager(static::loadSocialiteConfig());

            if ($this->config['state_store'] !== null) {
                $manager->setStateStore($this->config['state_store']);
            }

            $this->managerFactory = static fn (): Manager => $manager;
        }

        $manager = ($this->managerFactory)();

        if (!$manager instanceof Manager) {
            throw new \RuntimeException('The "manager" config/factory must return a ' . Manager::class . ' instance.');
        }

        // Inherit shared keys (intended-URL slot etc.) from the OAuth config
        // unless the app overrode them explicitly in socialite_auth.php.
        $scfg = $manager->config();
        if (!isset($this->explicit['intended_key']) && isset($scfg['intended_key'])) {
            $this->config['intended_key'] = (string) $scfg['intended_key'];
        }

        return $manager;
    }

    /**
     * The Auth guard this bridge logs into — password login stays fully
     * available through it: social_auth()->guard()->attempt(...).
     */
    public function guard(): AuthInterface
    {
        return $this->auth;
    }

    /**
     * Names of the providers currently usable (configured + enabled).
     * Handy for rendering "Login with Google/GitHub/..." buttons.
     *
     * @return string[]
     */
    public function enabledProviders(): array
    {
        return $this->socialite()->getProviders();
    }

    public function isConfigured(string $provider): bool
    {
        return $this->socialite()->hasProvider($provider);
    }

    /**
     * URL that starts the social login flow for a provider.
     */
    public function loginUrl(string $provider): string
    {
        return rtrim((string) $this->config['login_route'], '/') . '/' . strtolower($provider);
    }

    /* ------------------------------------------------------------------
     | Step 1: redirect to the provider
     * ------------------------------------------------------------------ */

    /**
     * Begin the social login: stash the intended post-login URL (optional)
     * and hand back the RedirectResponse toward the provider consent page.
     *
     * Controller usage:
     *   return social_auth()->redirect('google');
     */
    public function redirect(string $provider, ?string $intended = null): RedirectResponse
    {
        if ($intended !== null) {
            $this->rememberIntended($intended);
        }

        return $this->socialite()->driver($provider)->redirect();
    }

    /**
     * Remember where the user wanted to go before being bounced to the
     * provider (call from your auth middleware before redirecting guests).
     */
    public function rememberIntended(string $url): void
    {
        $session = $this->session();
        $session?->set_userdata([$this->config['intended_key'] => $url]);
    }

    /**
     * The remembered intended URL (if any); consumed on first read.
     */
    public function intendedUrl(bool $consume = true): ?string
    {
        $session = $this->session();
        if ($session === null) {
            return null;
        }

        $url = $session->userdata($this->config['intended_key']);

        if ($url !== null && $consume) {
            $session->unset_userdata($this->config['intended_key']);
        }

        return is_string($url) ? $url : null;
    }

    /* ------------------------------------------------------------------
     | Step 2: handle the callback — authenticate & log in
     * ------------------------------------------------------------------ */

    /**
     * Full callback handler: resolves the social user, finds/creates/links
     * the local account, logs it in through the shared guard and returns
     * the local user record.
     *
     *   $user = social_auth()->login('google');
     *   header('Location: ' . social_auth()->successUrl());
     *
     * @throws AuthException When resolution/registration is impossible and
     *                       config 'throw_on_error' is true.
     */
    public function login(string $provider, bool $remember = false): ?array
    {
        $provider = strtolower($provider);

        try {
            // A pending "connect to my profile" flow (see connect())?
            $linkNonce = $this->consumeLinkNonce($provider);

            $social = $this->user($provider);
            $user   = $this->authenticateSocialUser($social, $remember, $provider, $linkNonce);

            if ($user === null) {
                throw new AuthException(sprintf(
                    'No local account matches the %s profile "%s".',
                    $provider,
                    (string) ($social->getEmail() ?? $social->getId())
                ));
            }

            return $user;
        } catch (\Throwable $e) {
            $this->fire(AuthEvents::FAILED, [
                'identifier' => $provider,
                'reason'     => 'social_login_failed',
                'error'      => $e->getMessage(),
            ]);

            if ($this->config['throw_on_error']) {
                throw $e instanceof AuthException ? $e : new AuthException($e->getMessage(), 0, $e);
            }

            return null;
        }
    }

    /**
     * Read + clear the connect nonce stashed by connect(). Returns null for
     * a plain login flow. When present, authenticateSocialUser() links the
     * identity to the CURRENTLY LOGGED-IN account and refuses to hop to a
     * different one — the core safety property of "connect" vs "login".
     */
    protected function consumeLinkNonce(string $provider): ?string
    {
        $session = $this->session();
        $stored  = $session?->userdata($this->config['connect_key']);

        // No nonce stashed for this session => plain login flow. This also
        // covers the case where the OAuth state payload was consumed by a
        // prior validation pass on the same callback request: the session
        // nonce remains the authoritative signal that a connect() started.
        if (! is_string($stored) || $stored === '' || ! $this->guard()->check()) {
            return null;
        }

        // Cross-check against the validated state payload when available —
        // it must carry the very same nonce we minted server-side. When the
        // payload is unavailable or predates the nonce (e.g. it was already
        // pulled), fall back to the current logged-in user only: linking is
        // always scoped to THIS session's account, never another one.
        $driver = $this->socialite()->driver($provider);
        $state  = method_exists($driver, 'stateData') ? $driver->stateData() : null;
        $fromState = is_array($state) && isset($state['link_nonce'])
            ? (string) $state['link_nonce']
            : null;

        if ($fromState !== null && ! hash_equals($stored, $fromState)) {
            // State carries a DIFFERENT nonce than this session's — someone
            // replayed another flow's state. Refuse to link anything.
            return null;
        }

        // Single-use: burn the nonce so a second callback cannot re-link.
        $session?->unset_userdata($this->config['connect_key']);

        return $stored;
    }

    /**
     * Resolve ONLY the social user from the provider callback (no local
     * storage involved) — useful when you want custom matching logic.
     */
    public function user(string $provider): SocialiteUser
    {
        return $this->socialite()->driver($provider)->user();
    }

    /**
     * The OAuth token payload issued for the CURRENT callback request —
     * available right after user()/login() without re-exchanging the code.
     *
     * @return array{access_token?: string, refresh_token?: string, expires_in?: int, id_token?: string, ...}|null
     */
    public function accessTokenPayload(string $provider): ?array
    {
        $driver = $this->socialite()->driver(strtolower($provider));

        return method_exists($driver, 'accessTokenPayload')
            ? $driver->accessTokenPayload()
            : null;
    }

    /* ------------------------------------------------------------------
     | Token vault — persist & refresh provider tokens per account
     | ------------------------------------------------------------------ */

    /**
     * Persist the OAuth tokens issued for the CURRENT callback into the
     * user record's "social_tokens" column (a per-provider map), so the
     * app can call provider APIs later and renew access without a new
     * browser round-trip.
     *
     *   ['google' => ['access_token' => ..., 'refresh_token' => ...,
     *                'expires_at' => <epoch>, 'obtained_at' => <epoch>]]
     *
     * Only works when the configured guard provider implements
     * UpdatableProviderInterface; returns false otherwise. The current
     * session payload is kept in sync.
     */
    public function storeTokens(string $provider, ?array $user = null): bool
    {
        $provider = strtolower($provider);
        $payload  = $this->accessTokenPayload($provider);
        $user   ??= $this->guard()->user();

        if ($payload === null || ! is_array($user)) {
            return false;
        }

        $tokens = (array) ($user[self::TOKENS_COLUMN] ?? []);

        $record = [
            'obtained_at' => time(),
        ];
        foreach (['access_token', 'refresh_token', 'token_type', 'id_token', 'scope'] as $key) {
            if (isset($payload[$key]) && is_scalar($payload[$key])) {
                $record[$key] = (string) $payload[$key];
            }
        }
        // An absent expires_in means the provider token does not expire
        // (or the provider did not say) — store null, never a bogus value.
        $record['expires_at'] = isset($payload['expires_in'])
            ? time() + (int) $payload['expires_in']
            : null;

        // Providers like Google only send a NEW refresh_token on some
        // responses; keep the previous one when missing.
        if (! isset($record['refresh_token']) && isset($tokens[$provider]['refresh_token'])) {
            $record['refresh_token'] = $tokens[$provider]['refresh_token'];
        }

        $tokens[$provider] = $record;
        $user[self::TOKENS_COLUMN] = $tokens;

        $idColumn = (string) $this->guardConfig('id_column', 'id');
        $id       = $user[$idColumn] ?? null;

        if ($id !== null && $this->provider() instanceof UpdatableProviderInterface) {
            $this->provider()->updateUser($id, [self::TOKENS_COLUMN => $tokens]);
        }

        if ((int) ($this->guard()->id() ?? -1) === (int) ($id ?? -2)) {
            $this->guard()->setUser($user);
        }

        return true;
    }

    /**
     * Stored token record for a provider: ['access_token' => ..., ...] or
     * null when nothing was ever saved.
     *
     * @return array<string,mixed>|null
     */
    public function storedTokens(string $provider, ?array $user = null): ?array
    {
        $user ??= $this->guard()->user();

        if (! is_array($user)) {
            return null;
        }

        $record = $user[self::TOKENS_COLUMN][strtolower($provider)] ?? null;

        return is_array($record) ? $record : null;
    }

    /**
     * A usable access token for a provider: the stored one while it has
     * not expired, transparently renewed via refreshAccessToken() when it
     * has (and the renewal persisted back to storage). Null when no token
     * is available or renewal failed / no refresh token exists.
     */
    public function accessTokenFor(string $provider, bool $autoRefresh = true): ?string
    {
        $provider = strtolower($provider);
        $record   = $this->storedTokens($provider);

        if ($record === null || empty($record['access_token'])) {
            return null;
        }

        $expired = isset($record['expires_at']) && time() >= ((int) $record['expires_at'] - 60);

        if (! $expired || ! $autoRefresh) {
            return (string) $record['access_token'];
        }

        $refreshToken = $record['refresh_token'] ?? null;
        if (! is_string($refreshToken) || $refreshToken === '') {
            return null; // cannot renew — caller should re-run the flow
        }

        try {
            $fresh = $this->socialite()->driver($provider)->refreshAccessToken($refreshToken);
        } catch (\Throwable) {
            return null;
        }

        if (empty($fresh['access_token'])) {
            return null;
        }

        // Persist the renewed pair (keep old refresh token when absent).
        $user = $this->guard()->user();
        if (is_array($user)) {
            $tokens = (array) ($user[self::TOKENS_COLUMN] ?? []);
            $tokens[$provider] = array_merge($record, [
                'access_token'  => (string) $fresh['access_token'],
                'refresh_token' => (string) ($fresh['refresh_token'] ?? $refreshToken),
                'obtained_at'   => time(),
                'expires_at'    => isset($fresh['expires_in'])
                    ? time() + (int) $fresh['expires_in']
                    : null,
            ]);
            $user[self::TOKENS_COLUMN] = $tokens;

            $idColumn = (string) $this->guardConfig('id_column', 'id');
            $id       = $user[$idColumn] ?? null;
            if ($id !== null && $this->provider() instanceof UpdatableProviderInterface) {
                $this->provider()->updateUser($id, [self::TOKENS_COLUMN => $tokens]);
            }
            if ((int) ($this->guard()->id() ?? -1) === (int) ($id ?? -2)) {
                $this->guard()->setUser($user);
            }
        }

        return (string) $fresh['access_token'];
    }

    /* ------------------------------------------------------------------
     | Linked social accounts (profile page / settings)
     | ------------------------------------------------------------------ */

    /**
     * Providers currently linked to a local account, parsed from the
     * "social_accounts" column: ["google", "github"].
     *
     * @param  array|null $user Defaults to the logged-in user.
     * @return string[]
     */
    public function linkedProviders(?array $user = null): array
    {
        $column = (string) $this->config['social_accounts_column'];
        $refs   = $this->parseRefs($user[$column] ?? null);

        $names = [];
        foreach ($refs as $ref) {
            $provider = strtolower(explode(':', $ref, 2)[0]);
            if (! in_array($provider, $names, true)) {
                $names[] = $provider;
            }
        }

        return $names;
    }

    /**
     * Whether a given provider is already connected to the account.
     */
    public function isLinked(string $provider, ?array $user = null): bool
    {
        return in_array(strtolower($provider), $this->linkedProviders($user), true);
    }

    /**
     * Start a "connect this social account to my current profile" flow.
     *
     * A nonce is stored in the session and echoed back through the OAuth
     * state parameter (see withState()); on the callback login() detects it
     * and links the identity to the LOGGED-IN account instead of hopping
     * between accounts that happen to share an e-mail address.
     *
     * Guests get a plain login redirect (no linking possible).
     */
    public function connect(string $provider, ?string $intended = null): RedirectResponse
    {
        $provider = strtolower($provider);

        if (! $this->guard()->check()) {
            // Guests cannot link anything — behave like a normal login.
            return $this->redirect($provider, $intended);
        }

        $nonce = bin2hex(random_bytes(16));

        $session = $this->session();
        $session?->set_userdata([$this->config['connect_key'] => $nonce]);

        if ($intended !== null) {
            $this->rememberIntended($intended);
        }

        // The nonce rides along in the state payload (server-side store),
        // never in the URL, and comes back through stateData() on the
        // callback where login() consumes it.
        return $this->socialite()->driver($provider)
            ->withStateData(['link_nonce' => $nonce])
            ->redirect();
    }

    /**
     * Detach a social identity from an account (e.g. "Disconnect Google").
     * Password login keeps working; conversely, detaching the last login
     * channel is refused unless the account actually has a password set.
     *
     * @return array The updated user record.
     * @throws AuthException When the provider was not linked or removal
     *                       would lock the user out of their own account.
     */
    public function disconnect(string $provider, ?array $user = null): array
    {
        $provider = strtolower($provider);
        $user   ??= $this->guard()->user();

        if (! is_array($user)) {
            throw new AuthException('Cannot disconnect a social account: nobody is logged in.');
        }

        $column = (string) $this->config['social_accounts_column'];
        $refs   = $this->parseRefs($user[$column] ?? null);

        $kept = array_values(array_filter(
            $refs,
            static fn (string $ref): bool => explode(':', $ref, 2)[0] !== $provider
        ));

        if (count($kept) === count($refs)) {
            throw new AuthException("The [{$provider}] account is not connected to this user.");
        }

        // Don't let someone lock themselves out of a password-less account.
        $passwordCol = (string) $this->guardConfig('password_column', 'password');
        $hasPassword = trim((string) ($user[$passwordCol] ?? '')) !== '';

        if ($kept === [] && ! $hasPassword) {
            throw new AuthException(
                "Refusing to disconnect [{$provider}]: this account has no password and no other "
                . 'login method. Set a password (or connect another provider) first.'
            );
        }

        $update = [$column => $kept];
        $update[$provider . '_id'] = null;

        // Drop the stored OAuth tokens for the detached provider too.
        $tokens = (array) ($user[self::TOKENS_COLUMN] ?? []);
        unset($tokens[$provider]);
        $update[self::TOKENS_COLUMN] = $tokens;

        $idColumn = (string) $this->guardConfig('id_column', 'id');
        $id       = $user[$idColumn] ?? null;

        if ($id !== null && $this->provider() instanceof UpdatableProviderInterface) {
            $this->provider()->updateUser($id, $update);
        }

        $user = array_merge($user, $update);

        // Keep the live session payload in sync when editing the current user.
        if ((int) ($this->guard()->id() ?? -1) === (int) ($id ?? -2)) {
            $this->guard()->setUser($user);
        }

        $this->fire(AuthEvents::SOCIAL_DISCONNECT, [
            'user'     => $user,
            'provider' => $provider,
        ]);

        return $user;
    }

    /**
     * Find-or-create/link a local account for a SocialiteUser and log it in.
     * Returns the local user record, or null when no account could be used.
     *
     * @param string|null $provider Provider name; defaults to the value the
     *                              Socialite manager stamps on every user
     *                              (extra key "__provider").
     */
    public function authenticateSocialUser(SocialiteUser $social, bool $remember = false, ?string $provider = null, ?string $linkNonce = null): ?array
    {
        $provider = strtolower($provider ?? (string) ($social->getExtra('__provider') ?? ''));
        if ($provider === '') {
            throw new AuthException('Cannot resolve social identity: unknown provider.');
        }

        $column = (string) $this->config['social_accounts_column'];
        $ref    = $provider . ':' . (string) $social->getId();

        // 0) "Connect to my profile" flow (SocialiteAuth::connect()): the
        //    link nonce proves the browser session started this flow, so we
        //    attach the identity to the LOGGED-IN account — never another
        //    one that happens to share the e-mail address.
        if ($linkNonce !== null) {
            $current = $this->guard()->user();

            if (! is_array($current)) {
                throw new AuthException('Account-linking flow expired: please log in again and retry.');
            }

            if (in_array($ref, $this->parseRefs($current[$column] ?? null), true)) {
                // Already connected — just refresh the session and finish.
                $this->completeLogin($current, $provider, $social, $remember, false, false);
                return $current;
            }

            // Refuse to connect an identity that belongs to ANOTHER account.
            $owner = $this->findBySocialRef($ref);
            $idCol = (string) $this->guardConfig('id_column', 'id');
            if ($owner !== null && (int) ($owner[$idCol] ?? -1) !== (int) ($current[$idCol] ?? -2)) {
                throw new AuthException(
                    "This {$provider} account is already connected to a different user. "
                    . 'Log in with that account first, or disconnect it there.'
                );
            }

            $linked = $this->linkIdentity($current, $ref, $social);
            $this->auth->setUser($linked);

            $this->fire(AuthEvents::SOCIAL_LINK, [
                'user'      => $linked,
                'provider'  => $provider,
                'social_id' => $social->getId(),
            ]);
            $this->completeLogin($linked, $provider, $social, $remember, false, true);

            return $linked;
        }

        // 1) Existing account already linked to this exact social identity.
        $user = $this->findBySocialRef($ref);
        if ($user !== null) {
            $this->refreshProfile($user, $social, $column, $ref);
            $this->completeLogin($user, $provider, $social, $remember, false, false);
            return $user;
        }

        // 2) Existing account with the same e-mail -> link identities.
        $email = trim((string) ($social->getEmail() ?? ''));
        if ($email !== '' && $this->config['link_by_email'] && $this->emailLinkAllowed($provider, $social)) {
            $identifierCol = $this->identifierColumn();
            $existing = $this->provider()->retrieveByIdentifier($identifierCol, $email);

            if (is_array($existing)) {
                $existing = $this->linkIdentity($existing, $ref, $social);
                $this->completeLogin($existing, $provider, $social, $remember, false, true);
                return $existing;
            }
        }

        // 3) First-time login -> register a new account (random password;
        //    the user can set a real one later via password reset).
        if ($this->config['register_new_users']) {
            $id = $this->createUserFromSocial($provider, $social, $email);
            $fresh = $this->provider()->retrieveById($id);
            if ($fresh === null) {
                throw new AuthException('Account was created but could not be retrieved immediately.');
            }
            $this->completeLogin($fresh, $provider, $social, $remember, true, false);
            return $fresh;
        }

        return null;
    }

    /**
     * Convenience for controllers: perform the login and return the URL to
     * send the browser to (intended > configured redirect). Null signals
     * failure (caller may redirect to 'redirect_on_error').
     */
    public function complete(string $provider, bool $remember = false): ?string
    {
        if ($this->login($provider, $remember) === null) {
            return null;
        }

        return $this->successUrl();
    }

    /**
     * Post-login destination: the remembered intended URL when present,
     * otherwise config['redirect_after_login'].
     */
    public function successUrl(): string
    {
        return (string) ($this->intendedUrl() ?? $this->config['redirect_after_login'] ?? '/');
    }

    /* ------------------------------------------------------------------
     | Route handler — one entry point for both social endpoints
     | ------------------------------------------------------------------ */

    /**
     * Framework-agnostic controller action for the two routes that make up
     * the whole social login round-trip:
     *
     *   GET /auth/socialite/{provider}          -> start  (redirect to provider)
     *   GET /auth/socialite/{provider}/callback -> handle (login + redirect home)
     *
     * Wire it into ANY router/dispatcher without depending on one:
     *
     *   // single catch-all route (Laravel/CodeIgniter-ish style):
     *   Route::any('/auth/socialite/{provider}/{action?}',
     *       fn ($provider, $action = 'start') => social_auth()->handle($provider, $action));
     *
     *   // or plain PHP front controller:
     *   header('Location: ' . social_auth()->handle($provider, $action)->target);
     *
     * Options:
     *   - 'intended' : post-login destination to remember before bouncing
     *                  to the provider (start phase only).
     *   - 'connect'  : bool — force the "link to my current profile" flow
     *                  instead of a plain login (start phase only; guests
     *                  automatically fall back to normal login).
     *   - 'remember' : bool — keep the user signed in across sessions.
     *
     * @param  array{intended?:string|null,connect?:bool,remember?:bool} $options
     * @return RedirectResponse Always a redirect: to the provider (start),
     *                          to the intended/home page (success), or to
     *                          config['redirect_on_error'] (failure). Never
     *                          throws unless config['throw_on_error'] is on.
     */
    public function handle(string $provider, string $action = 'start', array $options = []): RedirectResponse
    {
        $provider = strtolower($provider);
        $action   = strtolower(trim($action, '/'));

        if (! $this->guard()->check() && ! $this->isConfigured($provider)) {
            return new RedirectResponse((string) $this->config['redirect_on_error']);
        }

        if ($action === 'callback') {
            $url = $this->complete($provider, (bool) ($options['remember'] ?? false));

            return new RedirectResponse(
                $url ?? (string) $this->config['redirect_on_error']
            );
        }

        // "start" (default): kick off the flow.
        $flow = ($options['connect'] ?? false) ? 'connect' : 'start';

        try {
            $response = $flow === 'connect'
                ? $this->connect($provider, $options['intended'] ?? null)
                : $this->redirect($provider, $options['intended'] ?? null);
        } catch (\Throwable $e) {
            // Unknown/unconfigured provider etc. — honour throw_on_error.
            if ($this->config['throw_on_error']) {
                throw $e instanceof AuthException ? $e : new AuthException($e->getMessage(), 0, $e);
            }

            return new RedirectResponse((string) $this->config['redirect_on_error']);
        }

        return $response instanceof RedirectResponse
            ? $response
            : new RedirectResponse((string) $response);
    }

    /* ------------------------------------------------------------------
     | Internals
     * ------------------------------------------------------------------ */

    /**
     * Shared tail of every successful social login: put the user through
     * the SAME guard as password logins (session payload, remember-me,
     * USER_LOGGED_IN event) and additionally emit SOCIAL_LOGIN so apps can
     * distinguish the channel.
     */
    protected function completeLogin(array $user, string $provider, SocialiteUser $social, bool $remember, bool $created, bool $linked): void
    {
        $this->auth->login($user, $remember);

        // Persist the freshly issued OAuth tokens next to the account so the
        // app can call provider APIs later (accessTokenFor()) — best effort.
        if ($this->config['store_tokens'] ?? true) {
            try {
                $this->storeTokens($provider, $user);
            } catch (\Throwable) {
                // Token storage must never break a successful login.
            }
        }

        $this->fire(AuthEvents::SOCIAL_LOGIN, [
            'user'      => $user,
            'provider'  => $provider,
            'social_id' => $social->getId(),
            'created'   => $created,
            'linked'    => $linked,
        ]);
    }

    /**
     * Read a guard config value, falling back to the Auth defaults when a
     * custom AuthInterface implementation does not expose getConfigValue().
     */
    protected function guardConfig(string $key, mixed $default = null): mixed
    {
        if (method_exists($this->auth, 'getConfigValue')) {
            return $this->auth->getConfigValue($key, $default);
        }
        return Auth::defaultConfig()[$key] ?? $default;
    }

    /**
     * Emit an event on the guard, falling back silently for guards without
     * the firePublic() helper.
     */
    protected function fire(string $event, array $payload): void
    {
        if (method_exists($this->auth, 'firePublic')) {
            $this->auth->firePublic($event, $payload);
        }
    }

    protected function createUserFromSocial(string $provider, SocialiteUser $social, string $email): int|string
    {
        $providerStorage = $this->provider();

        if (!$providerStorage instanceof RegisterableProviderInterface) {
            throw new AuthException(
                'Social registration requires the Auth provider to implement ' . RegisterableProviderInterface::class
            );
        }

        $identifierCol = $this->identifierColumn();
        $attributes    = [];

        if ($email !== '') {
            $attributes[$identifierCol] = $email;
        }

        $nameCol = (string) $this->config['name_column'];
        if ($nameCol !== '' && ($n = $social->getName()) !== null && $n !== '') {
            $attributes[$nameCol] = $n;
        }

        $avatarCol = (string) $this->config['avatar_column'];
        if ($avatarCol !== '' && ($a = $social->getAvatar()) !== null && $a !== '') {
            $attributes[$avatarCol] = $a;
        }

        $column = (string) $this->config['social_accounts_column'];
        if ($column !== '') {
            $attributes[$column] = [$provider . ':' . (string) $social->getId()];
        }

        if ($this->config['set_provider_id'] && $provider !== '') {
            $attributes[$provider . '_id'] = (string) $social->getId();
        }

        // A random password keeps the column non-empty; social users sign in
        // via the provider and can claim a password through resetPassword().
        $plain = bin2hex(random_bytes(24));

        if ($this->config['auto_verify_trusted']
            && in_array($provider, (array) $this->config['trusted_providers'], true)
            && $email !== ''
        ) {
            $verifyCol = (string) ($this->guardConfig('verify_column') ?? '');
            if ($verifyCol !== '') {
                $attributes[$verifyCol] = date('Y-m-d H:i:s');
            }
        }

        return $providerStorage->createUser($attributes, Auth::hashPassword($plain));
    }

    /**
     * Append the "<provider>:<id>" reference to an existing account.
     */
    protected function linkIdentity(array $user, string $ref, SocialiteUser $social): array
    {
        $column = (string) $this->config['social_accounts_column'];
        $refs   = $this->parseRefs($user[$column] ?? null);

        if (!in_array($ref, $refs, true)) {
            $refs[] = $ref;
            $update = [$column => $refs];

            if ($this->config['set_provider_id']) {
                $provider = explode(':', $ref, 2)[0];
                $update[$provider . '_id'] = (string) $social->getId();
            }

            $id = $user[$this->guardConfig('id_column', 'id')] ?? null;

            if ($id !== null && $this->provider() instanceof UpdatableProviderInterface) {
                $this->provider()->updateUser($id, $update);
                $user = array_merge($user, $update);
            }
            // Without an updatable provider we still log the user in for this
            // request; the link simply has to be persisted by the app layer.
        }

        return $user;
    }

    /**
     * Best-effort profile refresh (e.g. changed avatar/name) on every login.
     */
    protected function refreshProfile(array &$user, SocialiteUser $social, string $column, string $ref): void
    {
        $updates = [];

        $avatarCol = (string) $this->config['avatar_column'];
        $avatar    = $social->getAvatar();
        if ($avatarCol !== '' && is_string($avatar) && $avatar !== '' && ($user[$avatarCol] ?? null) !== $avatar) {
            $updates[$avatarCol] = $avatar;
            $user[$avatarCol]    = $avatar;
        }

        $refs = $this->parseRefs($user[$column] ?? null);
        if ($column !== '' && !in_array($ref, $refs, true)) {
            $refs[] = $ref;
            $updates[$column] = $refs;
            $user[$column]    = $refs;
        }

        if ($updates !== []) {
            $id = $user[$this->guardConfig('id_column', 'id')] ?? null;
            if ($id !== null && $this->provider() instanceof UpdatableProviderInterface) {
                $this->provider()->updateUser($id, $updates);
            }
        }
    }

    /**
     * Scan storage for an account whose social-accounts column contains the
     * given "<provider>:<id>" reference.
     *
     * Preference order: provider-side findUserBySocialRef() hook (efficient,
     * indexed lookup), then a retrieveByIdentifier('social_accounts', ...)
     * attempt, then a full scan when the provider exposes allUsers().
     */
    protected function findBySocialRef(string $ref): ?array
    {
        $storage = $this->provider();
        $column  = (string) $this->config['social_accounts_column'];

        if ($column === '') {
            return null;
        }

        if (method_exists($storage, 'findUserBySocialRef')) {
            $found = $storage->findUserBySocialRef($ref);
            return is_array($found) ? $found : null;
        }

        // Providers that index arbitrary columns may resolve the ref directly.
        $found = $storage->retrieveByIdentifier($column, $ref);
        if (is_array($found) && in_array($ref, $this->parseRefs($found[$column] ?? null), true)) {
            return $found;
        }

        if (method_exists($storage, 'allUsers')) {
            foreach ((array) $storage->allUsers() as $candidate) {
                if (is_array($candidate) && in_array($ref, $this->parseRefs($candidate[$column] ?? null), true)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    /**
     * Guard against e-mail-based account takeover: only link by e-mail when
     * the provider is trusted and (optionally) the address is verified.
     */
    protected function emailLinkAllowed(string $provider, SocialiteUser $social): bool
    {
        if (($this->config['require_verified_email_for_link'] ?? false) !== true) {
            return true;
        }

        if (!in_array($provider, (array) $this->config['trusted_providers'], true)) {
            return false;
        }

        return (bool) ($social->getExtra('email_verified') ?? false);
    }

    /**
     * Normalize a social-accounts value (array | "a,b" | null) to a list.
     *
     * @return string[]
     */
    protected function parseRefs(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('strval', $value), static fn ($v) => $v !== ''));
        }
        if (is_string($value) && $value !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $value)), static fn ($v) => $v !== ''));
        }
        return [];
    }

    protected function identifierColumn(): string
    {
        return (string) ($this->config['identifier_column'] ?? $this->guardConfig('identifier_column', 'email'));
    }

    protected function provider(): UserProviderInterface
    {
        return $this->auth->provider();
    }

    protected function session(): ?object
    {
        if ($this->sessionOverride !== null) {
            return $this->sessionOverride;
        }

        if (function_exists('get_instance')) {
            $ci = get_instance();
            if (is_object($ci) && isset($ci->session) && is_object($ci->session)) {
                return $ci->session;
            }
        }
        if (class_exists(\Kodhe\Framework\Session\Session::class)) {
            try {
                return new \Kodhe\Framework\Session\Session();
            } catch (\Throwable) {
                return null;
            }
        }
        return null;
    }

    /**
     * Load the OAuth provider config for the Socialite manager.
     *
     * @return array<string,mixed>
     */
    protected static function loadSocialiteConfig(): array
    {
        $candidates = [];

        if (defined('APPPATH')) {
            $candidates[] = APPPATH . 'config/socialite.php';
        }
        if (function_exists('config_path')) {
            $candidates[] = config_path('socialite.php');
        }
        if (function_exists('app_path')) {
            $candidates[] = app_path('config/socialite.php');
        }
        $candidates[] = dirname(__DIR__, 2) . '/socialite/config/socialite.php';

        foreach ($candidates as $path) {
            if (is_file($path)) {
                $config = require $path;
                if (is_array($config)) {
                    return $config;
                }
            }
        }

        return [];
    }
}
