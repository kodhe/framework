<?php

declare(strict_types=1);

namespace Kodhe\Pagination\ValueObjects;

/**
 * Link Data Value Object
 */
class LinkData
{
    private string $text;
    private string $url;
    private bool $isActive;
    private bool $isFirst;
    private bool $isLast;
    private bool $isPrevious;
    private bool $isNext;
    private int $pageNumber;
    private array $attributes;
    
    public function __construct(
        string $text,
        string $url = '',
        bool $isActive = false,
        bool $isFirst = false,
        bool $isLast = false,
        bool $isPrevious = false,
        bool $isNext = false,
        int $pageNumber = 0,
        array $attributes = []
    ) {
        $this->text = $text;
        $this->url = $url;
        $this->isActive = $isActive;
        $this->isFirst = $isFirst;
        $this->isLast = $isLast;
        $this->isPrevious = $isPrevious;
        $this->isNext = $isNext;
        $this->pageNumber = $pageNumber;
        $this->attributes = $attributes;
    }
    
    public function getText(): string
    {
        return $this->text;
    }
    
    public function getUrl(): string
    {
        return $this->url;
    }
    
    public function isActive(): bool
    {
        return $this->isActive;
    }
    
    public function isFirst(): bool
    {
        return $this->isFirst;
    }
    
    public function isLast(): bool
    {
        return $this->isLast;
    }
    
    public function isPrevious(): bool
    {
        return $this->isPrevious;
    }
    
    public function isNext(): bool
    {
        return $this->isNext;
    }
    
    public function getPageNumber(): int
    {
        return $this->pageNumber;
    }
    
    public function getAttributes(): array
    {
        return $this->attributes;
    }
    
    public function withUrl(string $url): self
    {
        $new = clone $this;
        $new->url = $url;
        return $new;
    }
    
    public function withAttributes(array $attributes): self
    {
        $new = clone $this;
        $new->attributes = $attributes;
        return $new;
    }
}
