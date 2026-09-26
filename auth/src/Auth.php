<?php

declare(strict_types=1);

namespace Kodhe\Framework\Auth;

use Kodhe\Framework\Auth\Contracts\AuthEventDispatcherInterface;
use Kodhe\Framework\Auth\Contracts\RegisterableProviderInterface;
use Kodhe\Framework\Auth\Contracts\UpdatableProviderInterface;
use Kodhe\Framework\Auth\Contracts\UserProviderInterface;

/**
 * Session-based authentication guard for Kodhe.
 *
 * Design notes:
 *  - The guard never talks to a database directly; it delegates user
 *    lookups to a UserProviderInterface implemented by the application.
 *  - Password hashing uses PHP's password_hash()/password_verify()
 *    (bcrypt by default, tunable via config).
 *  - Login state lives in the session under a configurable key and is
 *    re-validated against storage on every request (anti stale-role).
 *  - "Remember me" issues a random token stored in a cookie; only its
 *    SHA-256 hash is persisted server side so a DB leak cannot be
 *    replayed as a cookie.
 *  - Optional features (registration, password reset/change, email
 *    verification, throttling, events) activate automatically when the
 *    configured provider implements the matching optional contract.
 */
class Auth implements AuthInterface
{
    /** @var array Resolved configuration. */
    protected array $config;

    /** @var UserProviderInterface */
    protected UserProviderInterface $provider;

    /** @var object|null Session component (Kodhe\Session\Session or CI-compatible). */
    protected ?object $session;

    /** @var bool Whether cookies may be sent (false in CLI). */
    protected bool $cookiesEnabled;

    /** @var array|null Cached user record for the current request. */
    protected ?array $user = null;

    /** @var bool Whether we already tried to resolve the user this request. */
    protected bool $resolved = false;

    /** @var AuthEventDispatcherInterface|null Optional event bridge (hooks/PSR-14...). */
    protected ?AuthEventDispatcherInterface $events = null;

    /** @var callable|null Rate limiter: fn(string $key): int — returns recent-attempt count. */
    protected $rateLimiter = null;

    public function __construct(?array $config = null, ?UserProviderInterface $provider = null)
    {
        $this->config  = array_merge(self::defaultConfig(), $config ?? self::readAppConfig());
        $this->session = $this->resolveSession();
        $this->cookiesEnabled = !headers_sent() && \PHP_SAPI !== 'cli';

        if ($provider !== null) {
            $this->provider = $provider;
        } else {
            $class = $this->config['provider'];
            if (!is_string($class) || !class_exists($class)) {
                throw new \RuntimeException(
                    'Auth: set "provider" in application/config/auth.php to a class implementing UserProviderInterface.'
                );
            }
            $this->provider = new $class();
        }

        if (!$this->provider instanceof UserProviderInterface) {
            throw new \RuntimeException('Auth provider must implement ' . UserProviderInterface::class);
        }
    }

    /**
     * Sensible defaults; overridden by application/config/auth.php.
     */
    public static function defaultConfig(): array
    {
        return [
            'provider'          => null,                 // FQCN of the app's UserProvider
            'table_key'         => 'users',              // informational only
            'identifier_column' => 'email',              // column used to look up users at login
            'password_column'   => 'password',           // hashed-password column
            'id_column'         => 'id',
            'session_key'       => 'auth_user',          // session slot holding the auth payload
            'remember_cookie'   => 'kodhe_remember',
            'remember_seconds'  => 60 * 60 * 24 * 14,    // two weeks
            'remember_column'   => 'remember_token',     // column holding the (hashed) token
            'algo'              => PASSWORD_BCRYPT,
            'options'           => ['cost' => 11],
            'hash_remember'     => true,                 // store sha256(token) server-side
            'max_attempts'      => 5,                    // failed logins allowed per window (0 = no throttle)
            'lockout_seconds'   => 900,                  // throttle window in seconds
            'reset_ttl'         => 3600,                 // password-reset link lifetime (1 h)
            'verify_ttl'        => 86400 * 3,            // email-verification link lifetime (3 d)
            'verify_column'     => 'email_verified_at',  // non-null => verified
            'register_auto_login' => false,              // log the user straight in after register()
            'register_columns'  => [],                   // extra columns copied from input into the new record
        ];
    }

    /**
     * Attempt login with identifier + plain password.
     */
    public function attempt(string $identifier, string $password, bool $remember = false, array $extra = []): bool
    {
        if ($this->tooManyAttempts($identifier)) {
            $this->fire(AuthEvents::FAILED, ['identifier' => $identifier, 'reason' => 'throttled']);
            return false;
        }

        $user = $this->provider->retrieveByIdentifier(
            (string) $this->config['identifier_column'],
            $identifier,
            $extra
        );

        if (!is_array($user)) {
            // Equalize timing with the verify() branch below to reduce
            // user-enumeration through response-time probing. The dummy
            // hash must be a syntactically valid bcrypt digest, otherwise
            // password_verify() returns instantly and the timing
            // equalization silently fails.
            password_verify($password, '$2y$11$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy');
            $this->addFailedAttempt($identifier);
            $this->fire(AuthEvents::FAILED, ['identifier' => $identifier, 'reason' => 'unknown_user']);
            return false;
        }

        $hash = (string) ($user[$this->config['password_column']] ?? '');

        if ($hash === '' || !password_verify($password, $hash)) {
            $this->addFailedAttempt($identifier);
            $this->fire(AuthEvents::FAILED, ['identifier' => $identifier, 'reason' => 'bad_password']);
            return false;
        }

        // Transparent rehash when PHP's default cost/algorithm changed.
        if (password_needs_rehash($hash, $this->config['algo'], $this->config['options'])) {
            $this->setPassword((int) $user[$this->config['id_column']], $password);
        }

        $this->clearFailedAttempts($identifier);
        $this->login($user, $remember);
        $this->fire(AuthEvents::LOGIN, ['user' => $user, 'remember' => $remember]);
        return true;
    }

    /**
     * Log in a user record without password verification.
     */
    public function login(array|object $user, bool $remember = false): void
    {
        $user  = (array) $user;
        $idKey = $this->config['id_column'];

        unset($user[$this->config['password_column']]); // never keep hashes in session

        $payload = ['id' => $user[$idKey] ?? null, 'attributes' => $user];

        if ($this->session !== null) {
            $this->session->set_userdata([$this->config['session_key'] => $payload]);
        }

        $this->user     = $user;
        $this->resolved = true;

        if ($remember) {
            $this->issueRememberToken($user[$idKey] ?? null);
        }

        $this->fire(AuthEvents::USER_LOGGED_IN, ['user' => $user]);
    }

    /**
     * Refresh the cached user for the current request without touching
     * storage (useful after the application updates the user record).
     */
    public function setUser(array|object|null $user): void
    {
        $this->user     = $user === null ? null : array_diff_key((array) $user, [$this->config['password_column'] => null]);
        $this->resolved = true;
    }

    /**
     * Log out: clear session payload (+ optionally destroy session) and cookie.
     */
    public function logout(bool $destroySession = true): void
    {
        $id = $this->id();
        if ($id !== null) {
            $this->provider->updateRememberToken($id, null);
        }

        if ($this->session !== null) {
            $this->session->unset_userdata($this->config['session_key']);
            if ($destroySession) {
                @session_destroy();
            }
        }

        $this->forgetRememberCookie();
        $this->user     = null;
        $this->resolved = true;

        $this->fire(AuthEvents::LOGOUT, ['id' => $id]);
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Currently authenticated user record (fresh from provider), or null.
     */
    public function user(): ?array
    {
        if ($this->resolved) {
            return $this->user;
        }
        $this->resolved = true;
        $this->user     = $this->resolveUser();
        return $this->user;
    }

    /**
     * Get the user id, or one attribute of the user when a key is given.
     */
    public function id(?string $key = null): mixed
    {
        $user = $this->user();
        if ($user === null) {
            return null;
        }
        return $key === null ? ($user[$this->config['id_column']] ?? null) : ($user[$key] ?? null);
    }

    /**
     * Hash a password with the configured algorithm.
     */
    public static function hashPassword(string $password, ?string $algo = null, ?array $options = null): string
    {
        $algo    = $algo ?? PASSWORD_BCRYPT;
        $options = $options ?? ['cost' => 11];
        return password_hash($password, $algo, $options);
    }

    /**
     * Access the configured provider (e.g. for registration flows).
     */
    public function provider(): UserProviderInterface
    {
        return $this->provider;
    }

    // ------------------------------------------------------------------
    // Registration / password management / email verification
    // ------------------------------------------------------------------

    /**
     * Register a new user through a RegisterableProviderInterface.
     *
     * @param array $input Must contain the identifier column (e.g. "email")
     *                     and the plain-text password column. Extra columns
     *                     listed in config "register_columns" are copied.
     * @return int|string  The new user's id.
     * @throws AuthException When the provider cannot register, the account
     *                       already exists, or the password is empty.
     */
    public function register(array $input): int|string
    {
        if (!$this->provider instanceof RegisterableProviderInterface) {
            throw new AuthException(
                'register() requires a provider implementing ' . RegisterableProviderInterface::class
            );
        }

        $idCol  = (string) $this->config['identifier_column'];
        $pwCol  = (string) $this->config['password_column'];
        $plain  = (string) ($input[$pwCol] ?? '');

        if (!is_array($input) || count($input) === 0) {
            throw new AuthException('register(): input must be a non-empty array.');
        }
        if ($plain === '') {
            throw new AuthException('register(): a non-empty "' . $pwCol . '" is required.');
        }
        if (($input[$idCol] ?? null) === null || (string) $input[$idCol] === '') {
            throw new AuthException('register(): missing "' . $idCol . '" in input.');
        }

        if ($this->provider->hasIdentifier($idCol, (string) $input[$idCol])) {
            throw new AuthException('register(): an account with that ' . $idCol . ' already exists.');
        }

        $attributes = [];
        foreach ((array) $this->config['register_columns'] as $col) {
            if (array_key_exists($col, $input)) {
                $attributes[$col] = $input[$col];
            }
        }
        $attributes[$idCol] = $input[$idCol];

        $id = $this->provider->createUser($attributes, self::hashPassword($plain, $this->config['algo'], $this->config['options']));

        if (!empty($this->config['register_auto_login'])) {
            $user = $this->provider->retrieveById($id);
            if ($user !== null) {
                $this->login($user);
            }
        }

        return $id;
    }

    /**
     * Change the CURRENT user's password: old password must match.
     */
    public function changePassword(string $current, string $new): bool
    {
        $user = $this->user();
        if ($user === null) {
            throw new AuthException('changePassword(): no authenticated user.');
        }

        $record = $this->provider->retrieveById($user[$this->config['id_column']] ?? '');
        $hash   = (string) (($record[$this->config['password_column']] ?? ''));
        if ($record === null || $hash === '' || !password_verify($current, $hash)) {
            return false;
        }

        $this->applyPassword((int) $user[$this->config['id_column']], $new);
        return true;
    }

    /**
     * Start a password-reset flow: generates a single-use token, stores only
     * its hash (+ expiry) via the provider and returns the RAW token so the
     * application can e-mail it inside a reset link. Returns null when the
     * account does not exist — callers should show a generic message to
     * avoid user enumeration.
     */
    public function sendPasswordReset(string $identifierValue): ?string
    {
        $user = $this->provider->retrieveByIdentifier((string) $this->config['identifier_column'], $identifierValue);
        if (!is_array($user)) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $id    = $user[$this->config['id_column']];
        $ttl   = (int) $this->config['reset_ttl'];

        $this->updateProviderColumns($id, [
            'password_reset_hash'    => hash('sha256', $token),
            'password_reset_expires' => time() + $ttl,
        ]);

        $this->fire(AuthEvents::PASSWORD_RESET_REQUESTED, ['email' => $identifierValue, 'token' => $token]);
        return $token;
    }

    /**
     * Complete a password reset with the raw token from the e-mail link.
     */
    public function resetPassword(string $identifierValue, string $token, string $newPassword): bool
    {
        $user = $this->provider->retrieveByIdentifier((string) $this->config['identifier_column'], $identifierValue);
        if (!is_array($user) || $newPassword === '') {
            return false;
        }

        $stored = (string) ($user['password_reset_hash'] ?? '');
        $expires = (int) ($user['password_reset_expires'] ?? 0);

        if ($stored === '' || $expires < time() || !hash_equals($stored, hash('sha256', $token))) {
            $this->fire(AuthEvents::FAILED, ['identifier' => $identifierValue, 'reason' => 'bad_reset_token']);
            return false;
        }

        $id = $user[$this->config['id_column']];
        $this->applyPassword($id, $newPassword);
        // Invalidate the used token.
        $this->updateProviderColumns($id, ['password_reset_hash' => null, 'password_reset_expires' => null]);
        // And force re-logins everywhere: revoking the remember token logs
        // other sessions out on their next auto-login attempt.
        $this->provider->updateRememberToken($id, null);

        $this->fire(AuthEvents::PASSWORD_RESET, ['id' => $id]);
        return true;
    }

    /**
     * Issue an e-mail-verification token (raw value returned for the mail
     * link; only its hash + expiry are stored). Null when unknown account.
     */
    public function requestVerification(string $identifierValue): ?string
    {
        $user = $this->provider->retrieveByIdentifier((string) $this->config['identifier_column'], $identifierValue);
        if (!is_array($user)) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $id    = $user[$this->config['id_column']];

        $this->updateProviderColumns($id, [
            'verification_hash'    => hash('sha256', $token),
            'verification_expires' => time() + (int) $this->config['verify_ttl'],
        ]);

        $this->fire(AuthEvents::VERIFICATION_REQUESTED, ['email' => $identifierValue, 'token' => $token]);
        return $token;
    }

    /**
     * Verify an e-mail address using the token from the verification link.
     */
    public function verifyEmail(string $identifierValue, string $token): bool
    {
        $user = $this->provider->retrieveByIdentifier((string) $this->config['identifier_column'], $identifierValue);
        if (!is_array($user)) {
            return false;
        }

        $column  = (string) $this->config['verify_column'];
        if (!empty($user[$column])) {
            return true; // already verified
        }

        $stored  = (string) ($user['verification_hash'] ?? '');
        $expires = (int) ($user['verification_expires'] ?? 0);

        if ($stored === '' || $expires < time() || !hash_equals($stored, hash('sha256', $token))) {
            $this->fire(AuthEvents::FAILED, ['identifier' => $identifierValue, 'reason' => 'bad_verification_token']);
            return false;
        }

        $id = $user[$this->config['id_column']];
        $this->updateProviderColumns($id, [
            $column               => date('Y-m-d H:i:s'),
            'verification_hash'    => null,
            'verification_expires' => null,
        ]);
        $this->refreshCachedUser($id);

        $this->fire(AuthEvents::VERIFIED, ['id' => $id]);
        return true;
    }

    /**
     * Whether the current (or given) user record has a verified e-mail.
     */
    public function isVerified(?array $user = null): bool
    {
        $user ??= $this->user();
        if ($user === null) {
            return false;
        }
        return !empty($user[(string) $this->config['verify_column']]);
    }

    // ------------------------------------------------------------------
    // Throttling / events wiring
    // ------------------------------------------------------------------

    /**
     * Failed-login attempts recorded for an identifier within the window.
     */
    public function attempts(string $identifier): int
    {
        $key = $this->throttleKey($identifier);
        if ($this->rateLimiter !== null) {
            return (int) ($this->rateLimiter)($key);
        }
        return (int) ($this->session?->userdata($key) ?? 0);
    }

    /**
     * Seconds left before the identifier may attempt login again (0 = free).
     */
    public function retryAfter(string $identifier): int
    {
        if ((int) $this->config['max_attempts'] <= 0 || !$this->tooManyAttempts($identifier)) {
            return 0;
        }
        $resetAt = (int) ($this->session?->userdata($this->throttleKey($identifier) . '_reset') ?? 0);
        return max(0, $resetAt - time());
    }

    public function tooManyAttempts(string $identifier): bool
    {
        $max = (int) $this->config['max_attempts'];
        if ($max <= 0) {
            return false;
        }
        if ($this->attempts($identifier) < $max) {
            return false;
        }
        // Lockout expired? Reset the counter lazily.
        $resetAt = (int) ($this->session?->userdata($this->throttleKey($identifier) . '_reset') ?? 0);
        if ($resetAt > 0 && $resetAt < time()) {
            $this->clearFailedAttempts($identifier);
            return false;
        }
        return true;
    }

    public function addFailedAttempt(string $identifier): void
    {
        $key = $this->throttleKey($identifier);
        $n   = $this->attempts($identifier) + 1;

        if ($this->rateLimiter !== null) {
            // The external limiter owns counting; nothing else to do here.
            return;
        }
        if ($this->session !== null) {
            $this->session->set_userdata([
                $key            => $n,
                $key . '_reset' => time() + (int) $this->config['lockout_seconds'],
            ]);
        }
    }

    public function clearFailedAttempts(string $identifier): void
    {
        $key = $this->throttleKey($identifier);
        $this->session?->unset_userdata([$key, $key . '_reset']);
    }

    /**
     * Plug in an event dispatcher (hooks, PSR-14 adapter, ...).
     */
    public function setEventDispatcher(?AuthEventDispatcherInterface $dispatcher): static
    {
        $this->events = $dispatcher;
        return $this;
    }

    /**
     * Replace the default session-backed throttle counter with your own
     * cache/DB limiter: fn(string $key): int returning recent attempts.
     */
    public function setRateLimiter(?callable $limiter): static
    {
        $this->rateLimiter = $limiter;
        return $this;
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    /**
     * Emit an auth event through the optional dispatcher; never let a
     * broken listener take down authentication itself.
     */
    protected function fire(string $event, array $payload = []): void
    {
        try {
            $this->events?->dispatch($event, $payload);
        } catch (\Throwable) {
            // Swallow listener errors by design; wire logging in your app.
        }
    }

    protected function throttleKey(string $identifier): string
    {
        // mb_* may be unavailable (no mbstring ext); fall back to ASCII strtolower.
        $normalized = function_exists('mb_strtolower') ? mb_strtolower($identifier) : strtolower($identifier);
        return 'auth_throttle_' . hash('sha256', $normalized);
    }

    /**
     * Hash + persist a new password, requiring UpdatableProviderInterface.
     */
    protected function applyPassword(int|string $id, string $plain): void
    {
        $this->updateProviderColumns($id, [
            (string) $this->config['password_column'] => self::hashPassword($plain, $this->config['algo'], $this->config['options']),
        ]);
        $this->refreshCachedUser($id);
    }

    protected function updateProviderColumns(int|string $id, array $columns): void
    {
        if (!$this->provider instanceof UpdatableProviderInterface) {
            throw new AuthException(
                'This feature requires a provider implementing ' . UpdatableProviderInterface::class
            );
        }
        $this->provider->updateUser($id, $columns);
    }

    /**
     * Keep the in-session copy of the user fresh after a storage update.
     */
    protected function refreshCachedUser(int|string $id): void
    {
        if ($this->id() == $id) {
            $fresh = $this->provider->retrieveById($id);
            if ($fresh !== null) {
                unset($fresh[$this->config['password_column']]);
                $this->setUser($fresh);
                $this->session?->set_userdata([$this->config['session_key'] => ['id' => $id, 'attributes' => $fresh]]);
            }
        }
    }

    /**
     * Resolve the logged-in user from session, falling back to remember-me.
     */
    protected function resolveUser(): ?array
    {
        $payload = null;
        if ($this->session !== null) {
            $payload = $this->session->userdata($this->config['session_key']);
        }

        if (is_array($payload) && isset($payload['id'])) {
            // Re-fetch from storage so revoked/changed users are honoured.
            $user = $this->provider->retrieveById($payload['id']);
            if ($user !== null) {
                unset($user[$this->config['password_column']]); // never expose hashes via user()
                $this->fire(AuthEvents::USER_RETRIEVED, ['user' => $user]);
                return $user;
            }
            // Stale session (user deleted) — clean up.
            $this->session?->unset_userdata($this->config['session_key']);
            return null;
        }

        // No (valid) session payload: a remember-me cookie may still log in.
        // Drop any stale payload first so login() re-seeds the session and
        // subsequent requests don't short-circuit on the old id.
        if ($this->session !== null) {
            $this->session->unset_userdata($this->config['session_key']);
        }

        return $this->loginViaRememberCookie();
    }

    /**
     * Try to authenticate using the remember-me cookie.
     */
    protected function loginViaRememberCookie(): ?array
    {
        $name = $this->config['remember_cookie'];
        $raw  = $_COOKIE[$name] ?? null;
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        [$id, $token] = array_pad(explode(':', $raw, 2), 2, null);
        if ($id === null || $token === null || $id === '' || $token === '') {
            return null;
        }

        // Only accept well-formed ids/tokens: the id is looked up in storage
        // (keep it numeric-safe) and the token is a 64-char hex string.
        if (!preg_match('/^\d{1,20}$/', (string) $id) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            $this->forgetRememberCookie();
            return null;
        }

        $user = $this->provider->retrieveById((int) $id);
        if ($user === null) {
            $this->forgetRememberCookie();
            return null;
        }

        $column = (string) ($this->config['remember_column'] ?? 'remember_token');
        $stored = (string) ($user[$column] ?? '');
        $match  = $this->config['hash_remember'] ? hash_equals($stored, hash('sha256', $token)) : hash_equals($stored, $token);

        if (!$match) {
            $this->forgetRememberCookie();
            return null;
        }

        // Rotate the token on every auto-login (single-use tokens).
        $this->issueRememberToken((int) $id);
        unset($user[$this->config['password_column']]); // never keep hashes in memory/session
        $this->login($user, false);
        return $user;
    }

    /**
     * Generate + persist + send a fresh remember-me token.
     */
    protected function issueRememberToken(int|string|null $id): void
    {
        if ($id === null) {
            return;
        }
        $token  = bin2hex(random_bytes(32));
        $stored = $this->config['hash_remember'] ? hash('sha256', $token) : $token;

        $this->provider->updateRememberToken($id, $stored);

        if ($this->cookiesEnabled) {
            setcookie($this->config['remember_cookie'], $id . ':' . $token, [
                'expires'  => time() + (int) $this->config['remember_seconds'],
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => $this->runningOverHttps(),
            ]);
        }
    }

    protected function forgetRememberCookie(): void
    {
        if ($this->cookiesEnabled) {
            setcookie($this->config['remember_cookie'], '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => $this->runningOverHttps(),
            ]);
        }
    }

    protected function setPassword(int|string $id, string $plain): void
    {
        // Best-effort transparent rehash; providers may ignore if unsupported.
        try {
            $reflection = new \ReflectionMethod($this->provider, 'updatePassword');
            if ($reflection->isPublic()) {
                $this->provider->updatePassword($id, self::hashPassword($plain, $this->config['algo'], $this->config['options']));
            }
        } catch (\ReflectionException) {
            // Provider has no updatePassword(): skip rehash silently.
        }
    }

    /**
     * Load application/config/auth.php if present (returns an array).
     */
    protected static function readAppConfig(): array
    {
        if (defined('APPPATH')) {
            foreach (['auth.php', 'Auth.php'] as $file) {
                $path = APPPATH . 'config/' . $file;
                if (is_file($path)) {
                    $cfg = require $path;
                    if (is_array($cfg)) {
                        return $cfg;
                    }
                }
            }
        }
        return [];
    }

    /**
     * Locate the framework session object (CI super-object or container).
     */
    protected function resolveSession(): ?object
    {
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

    protected function runningOverHttps(): bool
    {
        return (($_SERVER['HTTPS'] ?? '') === 'on')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    }
}
