<?php

declare(strict_types=1);

namespace Kodhe\Framework\Auth;

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
        ];
    }

    /**
     * Attempt login with identifier + plain password.
     */
    public function attempt(string $identifier, string $password, bool $remember = false, array $extra = []): bool
    {
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
            return false;
        }

        $hash = (string) ($user[$this->config['password_column']] ?? '');

        if ($hash === '' || !password_verify($password, $hash)) {
            return false;
        }

        // Transparent rehash when PHP's default cost/algorithm changed.
        if (password_needs_rehash($hash, $this->config['algo'], $this->config['options'])) {
            $this->setPassword((int) $user[$this->config['id_column']], $password);
        }

        $this->login($user, $remember);
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
    // Internals
    // ------------------------------------------------------------------

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
