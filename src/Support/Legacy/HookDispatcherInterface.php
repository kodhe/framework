<?php

declare(strict_types=1);

namespace Kodhe\Framework\Support\Legacy;

/**
 * Minimal contract for the legacy CI3-style hook dispatcher.
 *
 * Packages that want to fire lifecycle hooks (pre_system, post_controller,
 * ...) can type-hint this interface instead of depending on the concrete
 * Hooks class, mirroring how AuthEventDispatcherInterface bridges the auth
 * guard to an event system. The framework registers its Hooks singleton
 * under the container service name 'hooks'; applications may bind their own
 * implementation (e.g. one that adapts hooks onto a PSR-14 dispatcher).
 */
interface HookDispatcherInterface
{
    /**
     * Call a named hook.
     *
     * @param string $which Hook name as declared in config/hooks.php.
     * @return bool TRUE when the hook exists and ran, FALSE otherwise.
     */
    public function call_hook($which = '');
}
