<?php

declare(strict_types=1);

/**
 * Test suite for the HookBridge PSR-14 adapter (kodhe/framework).
 * The psr/event-dispatcher interfaces are stubbed when not installed.
 */

namespace Psr\EventDispatcher {
    if (!interface_exists(EventDispatcherInterface::class, false)) {
        interface EventDispatcherInterface
        {
            public function dispatch(object $event): object;
        }
    }
}

namespace Kodhe\Framework\Support\Legacy {
    if (!interface_exists(HookDispatcherInterface::class, false)) {
        interface HookDispatcherInterface
        {
            public function call_hook($which = '');
        }
    }
}

namespace Kodhe\Framework\Support\Legacy\Bridge\Tests {

    use Kodhe\Framework\Support\Legacy\Bridge\HookBridge;
    use Kodhe\Framework\Support\Legacy\Bridge\HookEvent;

    require_once __DIR__ . '/../src/Support/Legacy/Bridge/HookEvent.php';
    require_once __DIR__ . '/../src/Support/Legacy/Bridge/HookBridge.php';

    class OrderConfirmed
    {
    }

    class FakeHooks
    {
        public array $called = [];
        public array $known = ['pre_system', 'post_controller'];

        public function call_hook($which = ''): bool
        {
            if ($which !== '' && in_array($which, $this->known, true)) {
                $this->called[] = $which;
                return true;
            }
            return false;
        }
    }

    class RecordingDispatcher implements \Psr\EventDispatcher\EventDispatcherInterface
    {
        public array $events = [];
        public function dispatch(object $event): object
        {
            $this->events[] = $event;
            return $event;
        }
    }

    $passed = 0;
    $failed = 0;

    function check(string $label, bool $condition): void
    {
        global $passed, $failed;
        if ($condition) { $passed++; echo "ok   - $label\n"; }
        else { $failed++; echo "FAIL - $label\n"; }
    }

    // hooks -> events mirroring
    $fake = new FakeHooks();
    $rec = new RecordingDispatcher();
    $bridge = new HookBridge($fake, $rec);

    check('call_hook returns TRUE for existing hook', $bridge->call_hook('pre_system') === true);
    check('legacy hook actually ran', $fake->called === ['pre_system']);
    check(
        'hook mirrored as hook.pre_system HookEvent with payload',
        count($rec->events) === 1
            && $rec->events[0] instanceof HookEvent
            && $rec->events[0]->name() === 'hook.pre_system'
            && $rec->events[0]->get('hook') === 'pre_system'
    );
    check('missing hook returns FALSE and emits nothing', $bridge->call_hook('missing') === false && count($rec->events) === 1);

    // setDispatcher late-binding + null detaches
    $rec2 = new RecordingDispatcher();
    $bridge2 = new HookBridge(new FakeHooks());
    $bridge2->call_hook('pre_system');
    check('no dispatcher attached means no events emitted', count($rec2->events) === 0);
    $bridge2->setDispatcher($rec2)->call_hook('pre_system');
    check('setDispatcher attaches dispatcher for later calls', count($rec2->events) === 1);

    // events -> hooks via asListener (name form, prefix stripped)
    $fake3 = new FakeHooks();
    $listener = (new HookBridge($fake3))->asListener();
    $listener(new HookEvent('hook.post_controller'));
    $listener(new HookEvent('pre_system'));
    check('asListener forwards event name (prefix stripped)', $fake3->called === ['post_controller', 'pre_system']);

    // events -> hooks via asListener (PSR-14 class-based object -> hook named
    // after the SHORT class name; unknown names are simply not recorded, so
    // we register a matching hook name in FakeHooks::$known)
    $fake4 = new FakeHooks();
    $fake4->known[] = 'OrderConfirmed';
    $listener4 = (new HookBridge($fake4))->asListener();
    $listener4(new OrderConfirmed());
    check('asListener maps class-based events to short class name hook', $fake4->called === ['OrderConfirmed']);

    // unknown forwarded names do not pollute the called list
    $fake5 = new FakeHooks();
    $l5 = (new HookBridge($fake5))->asListener();
    $l5(new HookEvent('hook.nope'));
    check('asListener on missing hook records nothing', $fake5->called === []);

    echo "\n$passed passed, $failed failed\n";
    exit($failed === 0 ? 0 : 1);
}
