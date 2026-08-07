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
    
    /**
     * @param string|int $text Text or page number (will be cast to string)
     * @param string $url URL for the link
     * @param bool $isActive Whether this is the current page
     * @param bool $isFirst Whether this is the first link
     * @param bool $isLast Whether this is the last link
     * @param bool $isPrevious Whether this is the previous link
     * @param bool $isNext Whether this is the next link
     * @param int $pageNumber Page number
     * @param array|string $attributes Attributes as array or string
     */
    public function __construct(
        $text,
        string $url = '',
        bool $isActive = false,
        bool $isFirst = false,
        bool $isLast = false,
        bool $isPrevious = false,
        bool $isNext = false,
        int $pageNumber = 0,
        $attributes = []
    ) {
        // Cast text to string to handle both string and int inputs
        $this->text = (string) $text;
        $this->url = $url;
        $this->isActive = $isActive;
        $this->isFirst = $isFirst;
        $this->isLast = $isLast;
        $this->isPrevious = $isPrevious;
        $this->isNext = $isNext;
        $this->pageNumber = $pageNumber;
        
        // Handle both array and string attributes (CI3 backward compatibility)
        if (is_string($attributes)) {
            $this->attributes = $this->_parseAttributesString($attributes);
        } elseif (is_array($attributes)) {
            $this->attributes = $attributes;
        } else {
            $this->attributes = [];
        }
    }
    
    /**
     * Parse attributes string to array (CI3 backward compatibility)
     */
    private function _parseAttributesString(string $attrString): array
    {
        $result = [];
        preg_match_all('/(\w+)="([^"]*)"/', $attrString, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $result[$match[1]] = $match[2];
        }
        return $result;
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
