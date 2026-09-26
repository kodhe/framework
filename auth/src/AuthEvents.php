<?php

declare(strict_types=1);

namespace Kodhe\Framework\Auth;

/**
 * Names of the events emitted by the Auth guard through an optional
 * AuthEventDispatcherInterface. Payload keys are documented per event.
 */
final class AuthEvents
{
    /** Login attempt failed. payload: {identifier, reason} */
    public const FAILED = 'auth.failed';

    /** A login succeeded. payload: {user, remember} */
    public const LOGIN = 'auth.login';

    /** The current user logged out. payload: {id} */
    public const LOGOUT = 'auth.logout';

    /** A user was logged in directly (no password check). payload: {user} */
    public const USER_LOGGED_IN = 'auth.user_logged_in';

    /** A session user was re-hydrated from storage. payload: {user} */
    public const USER_RETRIEVED = 'auth.user_retrieved';

    /** Password reset requested. payload: {email, token} */
    public const PASSWORD_RESET_REQUESTED = 'auth.password_reset_requested';

    /** Password actually changed / reset. payload: {id} */
    public const PASSWORD_RESET = 'auth.password_reset';

    /** Email verification requested. payload: {email, token} */
    public const VERIFICATION_REQUESTED = 'auth.verification_requested';

    /** Email verified. payload: {id} */
    public const VERIFIED = 'auth.verified';

    /** A social (OAuth) login succeeded. payload: {user, provider, social_id, created, linked} */
    public const SOCIAL_LOGIN = 'auth.social_login';

    /** Fired when a social identity is linked to an existing account. */
    public const SOCIAL_LINK = 'auth.social_link';

    /** Fired when a social identity is detached (settings page). */
    public const SOCIAL_DISCONNECT = 'auth.social_disconnect';

    /** Fired by Auth::authorize() when a permission check fails. */
    public const DENIED = 'auth.denied';

    private function __construct()
    {
        // Not instantiable — pure constant container.
    }
}
