<?php

declare(strict_types=1);

namespace Kodhe\Test\Contracts;

use Kodhe\Test\Result\AssertionResult;

/**
 * Interface for assertion implementations
 */
interface AssertionInterface
{
    /**
     * Execute the assertion
     *
     * @param mixed $test     The test value
     * @param mixed $expected The expected value
     * @param bool  $strict   Whether to use strict comparison
     * @return AssertionResult Result of the assertion
     */
    public function execute($test, $expected, bool $strict): AssertionResult;

    /**
     * Check if this assertion supports the given type
     *
     * @param mixed $expected The expected value/type
     * @return bool           Whether this assertion can handle it
     */
    public function supports($expected): bool;

    /**
     * Get the name of this assertion
     *
     * @return string Assertion name
     */
    public function getName(): string;
}
