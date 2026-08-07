<?php

declare(strict_types=1);

namespace Kodhe\Profiler\Collectors;

use Kodhe\Profiler\Contracts\CollectorInterface;

/**
 * Session Collector
 * 
 * Collects session data
 */
class SessionCollector implements CollectorInterface
{
    protected object $ci;
    protected ?array $sessionData = null;
    protected bool $hasSession = false;

    public function setDependencies(object $ci): void
    {
        $this->ci = $ci;
        $this->hasSession = isset($ci->session);
    }

    public function collect(): array
    {
        if ($this->sessionData !== null) {
            return $this->sessionData;
        }

        if (!$this->hasSession) {
            $this->sessionData = [];
            return $this->sessionData;
        }

        $this->sessionData = $this->ci->session->userdata() ?? [];
        return $this->sessionData;
    }

    public function hasData(): bool
    {
        if ($this->sessionData !== null) {
            return !empty($this->sessionData);
        }

        $this->collect();
        return !empty($this->sessionData);
    }

    public function getSectionName(): string
    {
        return 'session_data';
    }

    public function getSessionData(): array
    {
        if ($this->sessionData === null) {
            $this->collect();
        }
        return $this->sessionData;
    }

    public function hasSession(): bool
    {
        return $this->hasSession;
    }
}
