<?php

declare(strict_types=1);

namespace Kodhe\Test\Assertions;

use Kodhe\Test\Contracts\AssertionInterface;
use Kodhe\Test\Result\AssertionResult;
use Kodhe\Test\Support\TypeResolver;
use Kodhe\Test\ValueObjects\TestStatus;

/**
 * Assertion for type checking functions (is_string, is_int, etc.)
 */
class TypeAssertion implements AssertionInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute($test, $expected, bool $strict): AssertionResult
    {
        if (!TypeResolver::isTypeFunction($expected)) {
            throw new \InvalidArgumentException(
                sprintf('Expected must be a type function, got: %s', gettype($expected))
            );
        }

        try {
            $passed = TypeResolver::executeTypeFunction($expected, $test);
        } catch (\BadFunctionCallException $e) {
            return new AssertionResult(
                'Type Test',
                $expected,
                $test,
                'type',
                new TestStatus(TestStatus::FAIL),
                $e->getMessage()
            );
        }

        $status = new TestStatus($passed ? TestStatus::PASS : TestStatus::FAIL);
        $displayType = TypeResolver::getDisplayType($expected);

        return new AssertionResult(
            'Type Test',
            $expected,
            $test,
            'type',
            $status,
            $passed ? '' : sprintf('Type check failed: expected %s', $displayType)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function supports($expected): bool
    {
        return TypeResolver::isTypeFunction($expected);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'type';
    }
}
