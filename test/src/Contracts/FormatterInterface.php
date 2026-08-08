<?php

declare(strict_types=0);

namespace Kodhe\Framework\Test\Contracts;

use Kodhe\Framework\Test\Result\TestResultCollection;

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
