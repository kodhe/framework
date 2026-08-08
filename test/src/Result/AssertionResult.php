<?php

declare(strict_types=0);

namespace Kodhe\Framework\Test\Result;

use Kodhe\Framework\Test\ValueObjects\TestStatus;

/**
 * Value object representing a single assertion result
 */
class AssertionResult
{
    /**
     * @var string Test name
     */
    private $testName;

    /**
     * @var mixed Expected value
     */
    private $expected;

    /**
     * @var mixed Actual value
     */
    private $actual;

    /**
     * @var string Assertion type
     */
    private $assertionType;

    /**
     * @var TestStatus Pass/fail status
     */
    private $status;

    /**
     * @var string Result message
     */
    private $message;

    /**
     * @var array Additional metadata
     */
    private $metadata;

    /**
     * Constructor
     *
     * @param string     $testName      Name of the test
     * @param mixed      $expected      Expected value
     * @param mixed      $actual        Actual value
     * @param string     $assertionType Type of assertion
     * @param TestStatus $status        Pass/fail status
     * @param string     $message       Result message
     * @param array      $metadata      Additional metadata
     */
    public function __construct(
        string $testName,
        $expected,
        $actual,
        string $assertionType,
        TestStatus $status,
        string $message = '',
        array $metadata = []
    ) {
        $this->testName = $testName;
        $this->expected = $expected;
        $this->actual = $actual;
        $this->assertionType = $assertionType;
        $this->status = $status;
        $this->message = $message;
        $this->metadata = $metadata;
    }

    /**
     * Get test name
     *
     * @return string
     */
    public function getTestName(): string
    {
        return $this->testName;
    }

    /**
     * Get expected value
     *
     * @return mixed
     */
    public function getExpected()
    {
        return $this->expected;
    }

    /**
     * Get actual value
     *
     * @return mixed
     */
    public function getActual()
    {
        return $this->actual;
    }

    /**
     * Get assertion type
     *
     * @return string
     */
    public function getAssertionType(): string
    {
        return $this->assertionType;
    }

    /**
     * Get status
     *
     * @return TestStatus
     */
    public function getStatus(): TestStatus
    {
        return $this->status;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get metadata
     *
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Check if passed
     *
     * @return bool
     */
    public function isPassed(): bool
    {
        return $this->status->isPassed();
    }

    /**
     * Check if failed
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->status->isFailed();
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'test_name' => $this->testName,
            'expected' => $this->expected,
            'actual' => $this->actual,
            'assertion_type' => $this->assertionType,
            'result' => $this->status->getValue(),
            'message' => $this->message,
            'metadata' => $this->metadata,
        ];
    }
}
