<?php

declare(strict_types=1);

namespace Kodhe\Test\Result;

use Countable;
use IteratorAggregate;
use ArrayIterator;

/**
 * Collection of test results
 */
class TestResultCollection implements Countable, IteratorAggregate
{
    /**
     * @var TestResult[]
     */
    private $results = [];

    /**
     * Add a result to the collection
     *
     * @param TestResult $result The result to add
     * @return void
     */
    public function add(TestResult $result): void
    {
        $this->results[] = $result;
    }

    /**
     * Add multiple results
     *
     * @param array $results Array of TestResult objects
     * @return void
     */
    public function addAll(array $results): void
    {
        foreach ($results as $result) {
            if ($result instanceof TestResult) {
                $this->add($result);
            }
        }
    }

    /**
     * Get all results
     *
     * @return TestResult[]
     */
    public function getAll(): array
    {
        return $this->results;
    }

    /**
     * Get result by index
     *
     * @param int $index Result index
     * @return TestResult|null
     */
    public function get(int $index): ?TestResult
    {
        return $this->results[$index] ?? null;
    }

    /**
     * Get count of results
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->results);
    }

    /**
     * Get iterator
     *
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->results);
    }

    /**
     * Get number of passed tests
     *
     * @return int
     */
    public function getPassedCount(): int
    {
        $count = 0;
        foreach ($this->results as $result) {
            if ($result->isPassed()) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Get number of failed tests
     *
     * @return int
     */
    public function getFailedCount(): int
    {
        $count = 0;
        foreach ($this->results as $result) {
            if ($result->isFailed()) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Get summary
     *
     * @return array
     */
    public function getSummary(): array
    {
        return [
            'total' => $this->count(),
            'passed' => $this->getPassedCount(),
            'failed' => $this->getFailedCount(),
        ];
    }

    /**
     * Convert to array (legacy format)
     *
     * @return array
     */
    public function toArray(): array
    {
        $array = [];
        foreach ($this->results as $result) {
            $array[] = $result->toArray();
        }
        return $array;
    }

    /**
     * Create from array
     *
     * @param array $results Array of result arrays
     * @return self
     */
    public static function fromArray(array $results): self
    {
        $collection = new self();
        foreach ($results as $data) {
            if (is_array($data)) {
                $collection->add(new TestResult(
                    $data['test_name'] ?? 'undefined',
                    $data['test_datatype'] ?? 'unknown',
                    $data['res_datatype'] ?? 'unknown',
                    $data['result'] ?? 'failed',
                    $data['file'] ?? '',
                    $data['line'] ?? 0,
                    $data['notes'] ?? ''
                ));
            }
        }
        return $collection;
    }

    /**
     * Clear all results
     *
     * @return void
     */
    public function clear(): void
    {
        $this->results = [];
    }
}
