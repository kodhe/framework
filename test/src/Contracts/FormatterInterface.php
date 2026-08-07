<?php

declare(strict_types=1);

namespace Kodhe\Test\Contracts;

use Kodhe\Test\Result\TestResultCollection;

/**
 * Interface for formatter implementations
 */
interface FormatterInterface
{
    /**
     * Format test results
     *
     * @param TestResultCollection $results Collection of test results
     * @return string                       Formatted output
     */
    public function format(TestResultCollection $results): string;
}
