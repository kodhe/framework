# Kodhe Auth Component

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.1-blue.svg)](http://www.php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Session-based authentication guard with secure "remember me" for the Kodhe
Framework. The guard never touches the database directly: user lookups are
delegated to a `UserProviderInterface` implemented by your application, so the
component stays storage-agnostic (CodeIgniter models, PDO, an API — anything).

## Features

- **Guard + provider separation** — plug in any storage layer via `UserProviderInterface`
- **Session-backed login state**, re-validated against storage on every request (anti stale-role)
- **Remember-me cookies** using random tokens; only the SHA-256 hash is persisted server-side, and tokens rotate on every auto-login
- **Timing-equalized failed attempts** to blunt user-enumeration probes
- **Login throttling** (`max_attempts` / `lockout_seconds`) with session-backed counters or a pluggable rate limiter
- **Registration** via optional `RegisterableProviderInterface` (whitelisted columns, duplicate check, auto-login option)
- **Password change & reset** via optional `UpdatableProviderInterface` — single-use, hashed, expiring reset tokens; resetting revokes remember tokens
- **Email verification** with hashed, expiring tokens and an `email_verified_at` stamp
- **Auth events** (`auth.login`, `auth.failed`, `auth.password_reset`, …) through an optional event dispatcher bridge
- **Password hashing** via `password_hash()` (bcrypt cost 11 by default, fully tunable)
- **Password hashes stripped** from every path that exposes the user record
- **Three usage styles**: `Auth` object, `Facade` static proxy, or global `auth()` helper
- PSR-4 namespaced under `Kodhe\Framework\Auth\*`

## Installation

```bash
composer require kodhe/auth
```

### Requirements

- PHP >= 8.1
- A session component (`kodhe/session`, or CodeIgniter's `$this->session`)

## Quick Start

### 1. Implement a provider

```php
<?php

use Kodhe\Framework\Auth\Contracts\UserProviderInterface;

class EloquentUserProvider implements UserProviderInterface
{
    public function retrieveByIdentifier(string $identifier, string $value, array $extra = []): ?array
    {
        // $extra lets the guard enforce e.g. ['is_active' => 1] at login
        return $this->db->findUser($identifier, $value, $extra);
    }

    public function retrieveById(int|string $id): ?array
    {
        return $this->db->findUserById($id);
    }

    public function updateRememberToken(int|string $id, ?string $token): void
    {
        $this->db->setRememberToken($id, $token);
    }
}
```

### 2. Configure

Drop an array-returning config file at `application/config/auth.php`:

```php
<?php

return [
    'provider'          => EloquentUserProvider::class,
    'identifier_column' => 'email',      // column used at login
    'password_column'   => 'password',   // hashed-password column
    'id_column'         => 'id',
    'session_key'       => 'auth_user',
    'remember_cookie'   => 'kodhe_remember',
    'remember_seconds'  => 60 * 60 * 24 * 14,
    'algo'              => PASSWORD_BCRYPT,
    'options'           => ['cost' => 11],
];
```

All keys above already ship as defaults (`Auth::defaultConfig()`), so the file
only needs the entries you want to override.

### 3. Log users in

```php
<?php

use Kodhe\Framework\Auth\Facade as Auth;

if (Auth::attempt($email, $plainPassword, remember: true, extra: ['is_active' => 1])) {
    redirect('dashboard');
}

echo auth()->id('name');   // helper style — same singleton guard
```

## Usage

### Guard API

| Method | Description |
| --- | --- |
| `attempt(string $identifier, string $password, bool $remember = false, array $extra = []): bool` | Verify credentials and start a session |
| `login(array\|object $user, bool $remember = false): void` | Log in a known user record without a password check |
| `setUser(array\|object\|null $user): void` | Refresh the cached user (e.g. after a profile update) without touching storage |
| `logout(bool $destroySession = true): void` | Revoke the remember token and optionally destroy the session |
| `check(): bool` | Whether a user is authenticated |
| `user(): ?array` | The user record — **never** contains the password hash |
| `id(?string $key = null): mixed` | The user's id, or a single attribute when `$key` is given |
| `provider(): UserProviderInterface` | The configured provider (registration, password reset…) |
| `register(array $input): int|string` | Create a new user (needs `RegisterableProviderInterface`) |
| `changePassword(string $current, string $new): bool` | Change the logged-in user's password after verifying the old one |
| `sendPasswordReset(string $id): ?string` | Generate a reset token (returns raw token for the mail link, `null` if unknown account) |
| `resetPassword(string $id, string $token, string $new): bool` | Complete a reset; token is single-use + expiring, remember tokens revoked |
| `requestVerification(string $id): ?string` | Generate an email-verification token |
| `verifyEmail(string $id, string $token): bool` | Mark the email verified (stamps `email_verified_at`) |
| `isVerified(?array $user = null): bool` | Whether the current/given user has a verified email |
| `attempts/retryAfter/tooManyAttempts/clearFailedAttempts` | Login-throttling introspection & control |
| `setEventDispatcher($d)` / `setRateLimiter($fn)` | Optional wiring (see below) |
| `Auth::hashPassword(string $plain, ?string $algo = null, ?array $options = null): string` | Static helper for hashing new passwords |

### Optional provider contracts

Advanced features activate automatically when your provider implements them:

```php
use Kodhe\Framework\Auth\Contracts\{UserProviderInterface, RegisterableProviderInterface, UpdatableProviderInterface};

class AppUserProvider implements UserProviderInterface, RegisterableProviderInterface, UpdatableProviderInterface
{
    // ...retrieveByIdentifier / retrieveById / updateRememberToken...

    public function hasIdentifier(string $identifier, string $value): bool { /* SELECT 1 ... */ }
    public function createUser(array $attributes, string $passwordHash): int|string { /* INSERT, return id */ }
    public function updateUser(int|string $id, array $columns): void { /* UPDATE cols */ }
}
```

Suggested extra user-table columns used by these flows:

```sql
ALTER TABLE users ADD COLUMN password_reset_hash CHAR(64) NULL;
ALTER TABLE users ADD COLUMN password_reset_expires INT NULL;
ALTER TABLE users ADD COLUMN verification_hash CHAR(64) NULL;
ALTER TABLE users ADD COLUMN verification_expires INT NULL;
ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL;
```

Only `sha256(token)` is ever stored; the raw token goes into the e-mail link.

### Throttling

`attempt()` locks an identifier after `max_attempts` failures for
`lockout_seconds`. Counters live in the session by default; swap in a
cache/DB limiter:

```php
$auth->setRateLimiter(fn (string $key) => $cache->get($key, 0)); // return recent attempt count
$auth->retryAfter($email); // seconds until next try allowed
```

Set `'max_attempts' => 0` to disable.

### Events

Implement `AuthEventDispatcherInterface` and pass it to
`setEventDispatcher()` to receive `AuthEvents::*` notifications
(`LOGIN`, `FAILED`, `LOGOUT`, `PASSWORD_RESET_REQUESTED`, `VERIFIED`, …).
Listener exceptions are swallowed so they can never break authentication.

### Registration / reset / verification examples

```php
$id  = auth()->register(['email' => $e, 'password' => $pw, 'name' => $n]); // whitelist via register_columns
$tok = Auth::sendPasswordReset($e);            // put $tok in the reset e-mail link
Auth::resetPassword($e, $tok, $newPlain);      // handler for the link
$v   = Auth::requestVerification($e);          // e-mail confirmation link
Auth::verifyEmail($e, $v);                     // handler; auth()->isVerified() === true
```

### Helper and facade

The global `auth()` helper and `Kodhe\Framework\Auth\Facade` share one
singleton guard instance:

```php
auth()->check();                 // bool
auth('email');                   // single attribute of the current user
Facade::guard();                 // the underlying Auth instance
Facade::setGuard($custom);       // swap the guard (tests / custom config)
Facade::hashPassword('s3cret');  // bcrypt digest ready for storage
Facade::__callStatic(...)        // any other Auth method passes through
```

For tests, construct the guard directly instead of relying on the singleton:

```php
$auth = new \Kodhe\Framework\Auth\Auth(['provider' => FakeProvider::class]);
```

## Security Notes

- Only `sha256(token)` is stored server-side; a leaked DB row cannot be
  replayed as a cookie. Tokens rotate on each auto-login.
- Malformed remember cookies (bad `id:token` format) are rejected and cleared.
- Failed attempts run against a dummy bcrypt hash so response timing does not
  reveal whether the account exists.
- The deletion variant of the remember cookie sets the `secure` flag over HTTPS.

## Project Structure

```
auth/
├── composer.json
├── README.md
└── src/
    ├── Auth.php                          # the session guard (+ register/reset/verify/throttle)
    ├── AuthInterface.php                 # guard contract
    ├── AuthEvents.php                    # event-name constants emitted by the guard
    ├── AuthException.php                 # feature/config errors (unsupported provider etc.)
    ├── helpers.php                       # global auth() helper
    ├── Contracts/
    │   ├── UserProviderInterface.php     # storage bridge to implement
    │   ├── RegisterableProviderInterface.php  # optional: create users
    │   ├── UpdatableProviderInterface.php     # optional: update columns
    │   └── AuthEventDispatcherInterface.php   # optional: event bridge
    └── Support/
        └── Facade.php                    # static proxy over the guard
```

## License

MIT. Part of the Kodhe Framework ecosystem.
