<?php

declare(strict_types=1);

namespace Kodhe\Parser\ValueObjects;

use Kodhe\Parser\Contracts\TokenInterface;

/**
 * Token Value Object
 *
 * Represents a lexical token in the template.
 */
class Token implements TokenInterface
{
    /**
     * @param self::TYPE_* $type
     */
    public function __construct(
        private string $type,
        private string $value,
        private int $position,
        private ?string $name = null
    ) {}

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
