<?php

declare(strict_types=1);

namespace Kodhe\Test\Result;

/**
 * Value object representing a complete test result (legacy format compatible)
 */
class TestResult
{
    /**
     * @var string Test name
     */
    private $testName;

    /**
     * @var string Test datatype
     */
    private $testDatatype;

    /**
     * @var string Expected/Result datatype
     */
    private $resDatatype;

    /**
     * @var string Result status (passed/failed)
     */
    private $result;

    /**
     * @var string File name
     */
    private $file;

    /**
     * @var int Line number
     */
    private $line;

    /**
     * @var string Notes
     */
    private $notes;

    /**
     * Constructor
     *
     * @param string $testName     Name of the test
     * @param string $testDatatype Datatype of test value
     * @param string $resDatatype  Expected datatype
     * @param string $result       Pass/fail result
     * @param string $file         File name
     * @param int    $line         Line number
     * @param string $notes        Additional notes
     */
    public function __construct(
        string $testName,
        string $testDatatype,
        string $resDatatype,
        string $result,
        string $file = '',
        int $line = 0,
        string $notes = ''
    ) {
        $this->testName = $testName;
        $this->testDatatype = $testDatatype;
        $this->resDatatype = $resDatatype;
        $this->result = $result;
        $this->file = $file;
        $this->line = $line;
        $this->notes = $notes;
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
     * Get test datatype
     *
     * @return string
     */
    public function getTestDatatype(): string
    {
        return $this->testDatatype;
    }

    /**
     * Get result datatype
     *
     * @return string
     */
    public function getResDatatype(): string
    {
        return $this->resDatatype;
    }

    /**
     * Get result status
     *
     * @return string
     */
    public function getResult(): string
    {
        return $this->result;
    }

    /**
     * Get file name
     *
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * Get line number
     *
     * @return int
     */
    public function getLine(): int
    {
        return $this->line;
    }

    /**
     * Get notes
     *
     * @return string
     */
    public function getNotes(): string
    {
        return $this->notes;
    }

    /**
     * Check if passed
     *
     * @return bool
     */
    public function isPassed(): bool
    {
        return $this->result === 'passed';
    }

    /**
     * Check if failed
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->result === 'failed';
    }

    /**
     * Convert to array (legacy format)
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'test_name' => $this->testName,
            'test_datatype' => $this->testDatatype,
            'res_datatype' => $this->resDatatype,
            'result' => $this->result,
            'file' => $this->file,
            'line' => $this->line,
            'notes' => $this->notes,
        ];
    }

    /**
     * Create from assertion result
     *
     * @param AssertionResult $assertionResult The assertion result
     * @param string          $file            File name
     * @param int             $line            Line number
     * @param string          $notes           Notes
     * @return TestResult
     */
    public static function fromAssertionResult(
        AssertionResult $assertionResult,
        string $file = '',
        int $line = 0,
        string $notes = ''
    ): self {
        $expected = $assertionResult->getExpected();
        $actual = $assertionResult->getActual();

        // Determine expected type for display
        if (is_string($expected) && in_array($expected, [
            'is_object', 'is_string', 'is_bool', 'is_true', 'is_false',
            'is_int', 'is_numeric', 'is_float', 'is_double', 'is_array',
            'is_null', 'is_resource'
        ], true)) {
            $extype = str_replace(['true', 'false'], 'bool', str_replace('is_', '', $expected));
        } else {
            $extype = gettype($expected);
        }

        return new self(
            $assertionResult->getTestName(),
            gettype($actual),
            $extype,
            $assertionResult->getStatus()->getValue(),
            $file,
            $line,
            $notes
        );
    }
}
