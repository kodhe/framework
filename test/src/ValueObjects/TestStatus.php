<?php

declare(strict_types=0);

namespace Kodhe\Framework\Test\ValueObjects;

/**
 * Value object representing test status
 */
class TestStatus
{
    public const PASS = 'passed';
    public const FAIL = 'failed';

    /**
     * @var string
     */
    private $status;

    /**
     * Constructor
     *
     * @param string $status The status value
     * @throws \InvalidArgumentException If invalid status provided
     */
    public function __construct(string $status)
    {
        if (!in_array($status, [self::PASS, self::FAIL], true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid status: %s. Must be "%s" or "%s".', $status, self::PASS, self::FAIL)
            );
        }
        $this->status = $status;
    }

    /**
     * Get the status value
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->status;
    }

    /**
     * Check if test passed
     *
     * @return bool
     */
    public function isPassed(): bool
    {
        return $this->status === self::PASS;
    }

    /**
     * Check if test failed
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->status === self::FAIL;
    }

    /**
     * String representation
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->status;
    }
}
