<?php

declare(strict_types=1);

namespace Kodhe\Framework\Auth;

/**
 * Thrown by Auth when a requested feature needs provider support that the
 * configured UserProvider does not implement (e.g. calling register() with a
 * read-only provider). Catch it to degrade gracefully or surface a clear
 * configuration error to developers.
 */
class AuthException extends \RuntimeException
{
}
