<?php

declare(strict_types=1);

namespace Kodhe\Framework\Profiler\Collectors;

use Kodhe\Framework\Profiler\Contracts\CollectorInterface;

/**
 * URI Collector
 * 
 * Collects URI string data
 */
class UriCollector implements CollectorInterface
{
    protected object $ci;
    protected ?string $uriString = null;

    public function setDependencies(object $ci): void
    {
        $this->ci = $ci;
    }

    public function collect(): array
    {
        if ($this->uriString !== null) {
            return ['uri_string' => $this->uriString];
        }

        $this->uriString = $this->ci->uri->uri_string ?? '';

        return [
            'uri_string' => $this->uriString,
            'is_empty' => $this->uriString === ''
        ];
    }

    public function hasData(): bool
    {
        // Always has data (even if empty, we show "No URI")
        return true;
    }

    public function getSectionName(): string
    {
        return 'uri_string';
    }

    public function getUriString(): string
    {
        if ($this->uriString === null) {
            $this->collect();
        }
        return $this->uriString;
    }
}
