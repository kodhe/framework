<?php

declare(strict_types=1);

namespace Kodhe\Framework\Support\Legacy\Bridge;

use Kodhe\Framework\Support\Legacy\HookDispatcherInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Two-way adapter between the legacy CI3-style hook system and PSR-14 events.
 *
 * Lives in kodhe/framework (not kodhe/events) so the events package stays a
 * pure, dependency-free dispatcher usable anywhere: this bridge only needs
 * the framework's own HookDispatcherInterface plus the standard
 * psr/event-dispatcher contract — any PSR-14 implementation (kodhe/events,
 * Symfony, League, ...) can be plugged in.
 *
 * Hooks -> Events (recommended migration path):
 *
 *     $bridge = new HookBridge($hooks, $dispatcher);
 *     // When configured as the container 'hooks' service, every call_hook()
 *     // also fires an Event named "hook.<name>" on the dispatcher.
 *
 * Events -> Hooks (gradual adoption; let new event listeners reuse existing
 * hook definitions from config/hooks.php):
 *
 *     $dispatcher->dispatch(new \Kodhe\Framework\Events\Event('hook.post_controller'));
 *     // or forward any event back into hooks via $bridge->asListener().
 *
 * The bridge never modifies either system's behaviour; it only mirrors
 * signals, so you can migrate one hook at a time.
 */
class HookBridge implements HookDispatcherInterface
{
    public const EVENT_PREFIX = 'hook.';

    /**
     * @param object                     $hooks      Concrete legacy Hooks instance (or any object
     *                                                exposing call_hook()); typed loosely to avoid a
     *                                                hard dependency on kodhe/framework internals.
     * @param EventDispatcherInterface|null $dispatcher Any PSR-14 dispatcher that will receive
     *                                                mirrored "hook.<name>" events.
     */
    public function __construct(
        private readonly object $hooks,
        private ?EventDispatcherInterface $dispatcher = null,
    ) {
    }

    /**
     * Attach a dispatcher so call_hook() also emits PSR-14 events.
     */
    public function setDispatcher(?EventDispatcherInterface $dispatcher): static
    {
        $this->dispatcher = $dispatcher;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * Runs the legacy hook, then mirrors it as an event named "hook.<name>".
     */
    public function call_hook($which = ''): bool
    {
        $ran = (bool) $this->hooks->call_hook($which);

        if ($ran && $this->dispatcher !== null && $which !== '') {
            $this->dispatcher->dispatch(
                new HookEvent(self::EVENT_PREFIX . $which, ['hook' => $which])
            );
        }

        return $ran;
    }

    /**
     * Returns a listener that forwards any dispatched event back into the
     * legacy hook system, using the event name (stripped of the 'hook.'
     * prefix) as the hook name. For PSR-14 class-based events the short
     * class name is used instead. Useful while old hook definitions still
     * exist but code has started dispatching PSR-14 events.
     */
    public function asListener(): callable
    {
        return function (object $event): void {
            if ($event instanceof HookEvent || method_exists($event, 'name')) {
                $name = $event->name();

                if (str_starts_with($name, self::EVENT_PREFIX)) {
                    $name = substr($name, strlen(self::EVENT_PREFIX));
                }
            } else {
                $name = ($pos = strrpos($event::class, '\\')) === false
                    ? $event::class
                    : substr($event::class, $pos + 1);
            }

            $this->hooks->call_hook($name);
        };
    }
}
