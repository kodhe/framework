<?php

declare(strict_types=1);

namespace Kodhe\Framework\Auth\Contracts;

/**
 * Optional bridge to your application's event system (hooks, PSR-14, CI
 * events...). The guard emits the events listed in AuthEvents so the app can
 * log activity, invalidate sessions, or trigger alerts without the auth
 * component depending on any concrete dispatcher.
 */
interface AuthEventDispatcherInterface
{
    /**
     * @param string $event One of the Kodhe\Framework\Auth\AuthEvents constants.
     * @param array  $payload Event-specific data (see AuthEvents docblock).
     */
    public function dispatch(string $event, array $payload = []): void;
}
