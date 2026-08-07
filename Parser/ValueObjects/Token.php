<?php
/**
 * Token Value Object
 *
 * @package CodeIgniter\Parser\ValueObjects
 */

namespace CodeIgniter\Parser\ValueObjects;

use CodeIgniter\Parser\Contracts\TokenInterface;

class Token implements TokenInterface
{
    public const TYPE_TEXT = 'text';
    public const TYPE_VARIABLE = 'variable';
    public const TYPE_LOOP_START = 'loop_start';
    public const TYPE_LOOP_END = 'loop_end';
    public const TYPE_CONDITIONAL_START = 'conditional_start';
    public const TYPE_CONDITIONAL_END = 'conditional_end';
    public const TYPE_INCLUDE = 'include';

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $value;

    /**
     * @var int
     */
    private $position;

    /**
     * @param string $type
     * @param string $value
     * @param int    $position
     */
    public function __construct(string $type, string $value, int $position)
    {
        $this->type = $type;
        $this->value = $value;
        $this->position = $position;
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * Get variable name from value
     *
     * @return string
     */
    public function getVariableName(): string
    {
        if ($this->type === self::TYPE_VARIABLE) {
            return trim($this->value);
        }
        return '';
    }
}
