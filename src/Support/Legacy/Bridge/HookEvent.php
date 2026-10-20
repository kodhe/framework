<?php

declare(strict_types=1);

namespace Kodhe\Framework\Support\Legacy\Bridge;

/**
 * Minimal PSR-14-compatible event object emitted by HookBridge so the
 * bridge does not depend on any concrete dispatcher package (kodhe/events
 * or otherwise). It carries the mirrored hook name and payload.
 */
final class HookEvent
{
    public function __construct(
        private readonly string $name,
        private readonly array $payload = [],
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function get(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->payload;
        }

        return $this->payload[$key] ?? $default;
    }
}
