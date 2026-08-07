<?php

declare(strict_types=1);

namespace Kodhe\Parser\Contracts;

/**
 * Token Interface
 *
 * Represents a lexical token in the template.
 */
interface TokenInterface
{
    public const TYPE_VARIABLE = 'variable';
    public const TYPE_TAG_PAIR_OPEN = 'tag_pair_open';
    public const TYPE_TAG_PAIR_CLOSE = 'tag_pair_close';
    public const TYPE_TEXT = 'text';

    /**
     * Get token type
     */
    public function getType(): string;

    /**
     * Get token value
     */
    public function getValue(): string;

    /**
     * Get token position
     */
    public function getPosition(): int;

    /**
     * Get token name (for variables and tag pairs)
     */
    public function getName(): ?string;
}
