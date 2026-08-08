<?php

declare(strict_types=0);

namespace Kodhe\Framework\Test\Assertions;

use Kodhe\Framework\Test\Contracts\AssertionInterface;
use Kodhe\Framework\Test\Result\AssertionResult;
use Kodhe\Framework\Test\Support\ValueComparator;
use Kodhe\Framework\Test\ValueObjects\TestStatus;

/**
 * Assertion for equality comparison (loose)
 */
class EqualsAssertion implements AssertionInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute($test, $expected, bool $strict): AssertionResult
    {
        if ($strict) {
            $passed = ValueComparator::strictEquals($test, $expected);
        } else {
            $passed = ValueComparator::equals($test, $expected);
        }

        $status = new TestStatus($passed ? TestStatus::PASS : TestStatus::FAIL);

        return new AssertionResult(
            'Equality Test',
            $expected,
            $test,
            'equals',
            $status,
            $passed ? '' : 'Values are not equal'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function supports($expected): bool
    {
        // This is the default assertion for non-type-function values
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'equals';
    }
}
