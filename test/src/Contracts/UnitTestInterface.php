<?php

declare(strict_types=1);

namespace Kodhe\Framework\Test\Contracts;

/**
 * Interface for Unit Test implementations
 */
interface UnitTestInterface
{
    /**
     * Run a test
     *
     * @param mixed  $test        The test value
     * @param mixed  $expected    The expected value or type function
     * @param string $test_name   The test name
     * @param string $notes       Additional notes
     * @return string|false       Report output or false if inactive
     */
    public function run($test, $expected = true, string $test_name = 'undefined', string $notes = '');

    /**
     * Generate a report
     *
     * @param array $result Test results to report
     * @return string       Formatted report
     */
    public function report(array $result = []): string;

    /**
     * Get raw result data
     *
     * @param array $results Specific results to return
     * @return array         Array of result data
     */
    public function result(array $results = []): array;

    /**
     * Set visible test items
     *
     * @param array $items Items to display in results
     * @return void
     */
    public function set_test_items(array $items): void;

    /**
     * Enable/disable strict comparison
     *
     * @param bool $state Whether to use strict comparison
     * @return void
     */
    public function use_strict(bool $state = true): void;

    /**
     * Enable/disable unit testing
     *
     * @param bool $state Whether testing is active
     * @return void
     */
    public function active(bool $state = true): void;

    /**
     * Set the template for report output
     *
     * @param string $template Template string
     * @return void
     */
    public function set_template(string $template): void;

    /**
     * Reset all test results
     *
     * @return void
     */
    public function reset(): void;
}
