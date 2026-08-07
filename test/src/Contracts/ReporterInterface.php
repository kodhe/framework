<?php

declare(strict_types=1);

namespace Kodhe\Test\Contracts;

use Kodhe\Test\Result\TestResultCollection;

/**
 * Interface for reporter implementations
 */
interface ReporterInterface
{
    /**
     * Report test results
     *
     * @param TestResultCollection $results Collection of test results
     * @return string                       Formatted report output
     */
    public function report(TestResultCollection $results): string;
}
