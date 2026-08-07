<?php

declare(strict_types=1);

namespace Kodhe\Parser\Context;

/**
 * Parse Context
 *
 * Holds context data during template parsing.
 */
class ParseContext
{
    private string $lDelim = '{';
    private string $rDelim = '}';
    
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    private int $loopDepth = 0;
    private bool $cacheEnabled = true;

    public function __construct(
        string $lDelim = '{',
        string $rDelim = '}',
        array $data = []
    ) {
        $this->lDelim = $lDelim;
        $this->rDelim = $rDelim;
        $this->data = $data;
    }

    public function getLDELIM(): string
    {
        return $this->lDelim;
    }

    public function getRDELIM(): string
    {
        return $this->rDelim;
    }

    public function setDelimiters(string $l, string $r): void
    {
        $this->lDelim = $l;
        $this->rDelim = $r;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @param string|int $key
     */
    public function get($key): mixed
    {
        return $this->data[$key] ?? null;
    }

    /**
     * @param string|int $key
     */
    public function has($key): bool
    {
        return isset($this->data[$key]);
    }

    public function getLoopDepth(): int
    {
        return $this->loopDepth;
    }

    public function enterLoop(): void
    {
        $this->loopDepth++;
    }

    public function exitLoop(): void
    {
        if ($this->loopDepth > 0) {
            $this->loopDepth--;
        }
    }

    public function isCacheEnabled(): bool
    {
        return $this->cacheEnabled;
    }

    public function setCacheEnabled(bool $enabled): void
    {
        $this->cacheEnabled = $enabled;
    }

    /**
     * Create a child context for nested loops
     */
    public function createChild(array $childData): self
    {
        $child = new self($this->lDelim, $this->rDelim, $childData);
        $child->loopDepth = $this->loopDepth + 1;
        $child->cacheEnabled = $this->cacheEnabled;
        
        return $child;
    }
}
