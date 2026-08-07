<?php

declare(strict_types=1);

namespace Kodhe\Pagination\ValueObjects;

/**
 * Immutable-ish value object describing a pagination link.
 *
 * The public properties are kept for backward compatibility with the
 * original library. Getter/helper methods are provided for renderers so
 * renderers do not need to know the internal property names.
 */
class LinkData
{
    public string $text;
    public ?string $url;
    public bool $isCurrent;
    public bool $isDisabled;
    public bool $isBreak;
    public string $type;
    public int $pageNumber;
    public array $attributes;

    /**
     * @param array<string, mixed> $extraClasses
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        string $text,
        ?string $url = null,
        bool $isCurrent = false,
        bool $isDisabled = false,
        bool $isBreak = false,
        string $type = 'page',
        int $pageNumber = 0,
        array $extraClasses = [],
        array $attributes = []
    ) {
        $this->text = $text;
        $this->url = $url;
        $this->isCurrent = $isCurrent;
        $this->isDisabled = $isDisabled;
        $this->isBreak = $isBreak;
        $this->type = $type;
        $this->pageNumber = $pageNumber;

        $this->attributes = $attributes;

        if ($extraClasses !== []) {
            $classNames = [];

            foreach ($extraClasses as $class) {
                if (is_string($class) && trim($class) !== '') {
                    $classNames[] = trim($class);
                }
            }

            if ($classNames !== []) {
                $classString = implode(' ', $classNames);

                if (
                    isset($this->attributes['class']) &&
                    is_string($this->attributes['class']) &&
                    trim($this->attributes['class']) !== ''
                ) {
                    $this->attributes['class'] .= ' ' . $classString;
                } else {
                    $this->attributes['class'] = $classString;
                }
            }
        }
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getUrl(): string
    {
        return $this->url ?? '#';
    }

    public function getRawUrl(): ?string
    {
        return $this->url;
    }

    public function getPageNumber(): int
    {
        return $this->pageNumber;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Renderer compatibility helper.
     *
     * "Active" means the current page.
     */
    public function isActive(): bool
    {
        return $this->isCurrent;
    }

    public function isCurrent(): bool
    {
        return $this->isCurrent;
    }

    public function isDisabled(): bool
    {
        return $this->isDisabled;
    }

    public function isBreak(): bool
    {
        return $this->isBreak || $this->type === 'break';
    }

    public function isFirst(): bool
    {
        return $this->type === 'first';
    }

    public function isLast(): bool
    {
        return $this->type === 'last';
    }

    public function isPrevious(): bool
    {
        return $this->type === 'prev';
    }

    public function isNext(): bool
    {
        return $this->type === 'next';
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function hasUrl(): bool
    {
        return $this->url !== null && $this->url !== '';
    }
}