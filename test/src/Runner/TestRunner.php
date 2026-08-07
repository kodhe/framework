<?php

declare(strict_types=1);

namespace Kodhe\Test\Runner;

use Kodhe\Test\Contracts\AssertionInterface;
use Kodhe\Test\Result\AssertionResult;
use Kodhe\Test\Result\TestResult;
use Kodhe\Test\Result\TestResultCollection;
use Kodhe\Test\Support\TypeResolver;
use Kodhe\Test\Support\ValueComparator;
use Kodhe\Test\ValueObjects\TestStatus;
use Kodhe\Test\Assertions\TypeAssertion;
use Kodhe\Test\Assertions\EqualsAssertion;

/**
 * Test runner that executes tests and collects results
 */
class TestRunner
{
    /**
     * @var TypeAssertion
     */
    private $typeAssertion;

    /**
     * @var EqualsAssertion
     */
    private $equalsAssertion;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->typeAssertion = new TypeAssertion();
        $this->equalsAssertion = new EqualsAssertion();
    }

    /**
     * Run a single test
     *
     * @param mixed  $test      The test value
     * @param mixed  $expected  The expected value or type function
     * @param string $testName  The test name
     * @param bool   $strict    Whether to use strict comparison
     * @param string $file      File name
     * @param int    $line      Line number
     * @param string $notes     Additional notes
     * @return TestResult       Result of the test
     */
    public function run(
        $test,
        $expected = true,
        string $testName = 'undefined',
        bool $strict = false,
        string $file = '',
        int $line = 0,
        string $notes = ''
    ): TestResult {
        $assertionResult = $this->executeAssertion($test, $expected, $strict, $testName);

        return TestResult::fromAssertionResult(
            $assertionResult,
            $file,
            $line,
            $notes
        );
    }

    /**
     * Execute an assertion
     *
     * @param mixed  $test     The test value
     * @param mixed  $expected The expected value or type function
     * @param bool   $strict   Whether to use strict comparison
     * @param string $testName The test name
     * @return AssertionResult
     */
    private function executeAssertion(
        $test,
        $expected,
        bool $strict,
        string $testName
    ): AssertionResult {
        // Select appropriate assertion
        if ($this->typeAssertion->supports($expected)) {
            return $this->typeAssertion->execute($test, $expected, $strict);
        }

        return $this->equalsAssertion->execute($test, $expected, $strict);
    }
}
