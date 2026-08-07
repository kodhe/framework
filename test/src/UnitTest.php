<?php

declare(strict_types=1);

namespace Kodhe\Test;

use Kodhe\Test\Contracts\UnitTestInterface;
use Kodhe\Test\Reporters\DefaultReporter;
use Kodhe\Test\Result\TestResult;
use Kodhe\Test\Result\TestResultCollection;
use Kodhe\Test\Runner\TestRunner;

/**
 * Unit Testing Class
 *
 * Simple testing class - Compatibility Facade for CodeIgniter 3 API
 *
 * @package         CodeIgniter
 * @subpackage      Libraries
 * @category        UnitTesting
 * @author          EllisLab Dev Team
 * @link            https://codeigniter.com/user_guide/libraries/unit_testing.html
 */
class UnitTest implements UnitTestInterface
{
    /**
     * Active flag
     *
     * @var bool
     */
    public $active = true;

    /**
     * Test results
     *
     * @var array
     */
    public $results = [];

    /**
     * Strict comparison flag
     *
     * Whether to use === or == when comparing
     *
     * @var bool
     */
    public $strict = false;

    /**
     * Template
     *
     * @var string|null
     */
    protected $_template = null;

    /**
     * Template rows
     *
     * @var string|null
     */
    protected $_template_rows = null;

    /**
     * List of visible test items
     *
     * @var array
     */
    protected $_test_items_visible = [
        'test_name',
        'test_datatype',
        'res_datatype',
        'result',
        'file',
        'line',
        'notes'
    ];

    /**
     * Internal test runner
     *
     * @var TestRunner|null
     */
    private $runner;

    /**
     * Internal result collection
     *
     * @var TestResultCollection|null
     */
    private $collection;

    /**
     * Internal reporter
     *
     * @var DefaultReporter|null
     */
    private $reporter;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        log_message('info', 'Unit Testing Class Initialized');
    }

    /**
     * Get internal runner (lazy initialization)
     *
     * @return TestRunner
     */
    private function getRunner(): TestRunner
    {
        if ($this->runner === null) {
            $this->runner = new TestRunner();
        }
        return $this->runner;
    }

    /**
     * Get internal collection (lazy initialization)
     *
     * @return TestResultCollection
     */
    private function getCollection(): TestResultCollection
    {
        if ($this->collection === null) {
            $this->collection = new TestResultCollection();
        }
        return $this->collection;
    }

    /**
     * Get internal reporter (lazy initialization)
     *
     * @return DefaultReporter
     */
    private function getReporter(): DefaultReporter
    {
        if ($this->reporter === null) {
            $this->reporter = new DefaultReporter();
        }
        return $this->reporter;
    }

    // --------------------------------------------------------------------

    /**
     * Run the tests
     *
     * Runs the supplied tests
     *
     * @param mixed  $test      The test value
     * @param mixed  $expected  The expected value or type function
     * @param string $test_name The test name
     * @param string $notes     Additional notes
     * @return string|false     Report output or false if inactive
     */
    public function run($test, $expected = true, $test_name = 'undefined', $notes = '')
    {
        if ($this->active === false) {
            return false;
        }

        // Get backtrace information
        $back = $this->_backtrace();

        // Run test using internal runner
        $testResult = $this->getRunner()->run(
            $test,
            $expected,
            $test_name,
            $this->strict,
            $back['file'],
            (int) $back['line'],
            $notes
        );

        // Add to internal collection
        $this->getCollection()->add($testResult);

        // Store in legacy format
        $this->results[] = $testResult->toArray();

        // Return report
        return $this->report([$testResult->toArray()]);
    }

    // --------------------------------------------------------------------

    /**
     * Generate a report
     *
     * Displays a table with the test data
     *
     * @param array $result Test results to report
     * @return string       Formatted report
     */
    public function report($result = [])
    {
        if (count($result) === 0) {
            $result = $this->result();
        }

        // Use language for labels
        $lang = kodhe()->load->language('unit_test');

        $this->_parse_template();

        $r = '';
        foreach ($result as $res) {
            $table = '';

            foreach ($res as $key => $val) {
                // Apply color coding for result
                if ($key === kodhe()->lang->line('ut_result')) {
                    if ($val === kodhe()->lang->line('ut_passed')) {
                        $val = '<span style="color: #0C0;">' . $val . '</span>';
                    } elseif ($val === kodhe()->lang->line('ut_failed')) {
                        $val = '<span style="color: #C00;">' . $val . '</span>';
                    }
                }

                $table .= str_replace(['{item}', '{result}'], [$key, $val], $this->_template_rows);
            }

            $r .= str_replace('{rows}', $table, $this->_template);
        }

        return $r;
    }

    // --------------------------------------------------------------------

    /**
     * Use strict comparison
     *
     * Causes the evaluation to use === rather than ==
     *
     * @param bool $state Whether to use strict comparison
     * @return void
     */
    public function use_strict($state = true)
    {
        $this->strict = (bool) $state;
    }

    // --------------------------------------------------------------------

    /**
     * Make Unit testing active
     *
     * Enables/disables unit testing
     *
     * @param bool $state Whether testing is active
     * @return void
     */
    public function active($state = true)
    {
        $this->active = (bool) $state;
    }

    // --------------------------------------------------------------------

    /**
     * Result Array
     *
     * Returns the raw result data
     *
     * @param array $results Specific results to return
     * @return array        Array of result data
     */
    public function result($results = [])
    {
        // Use language for labels
        kodhe()->load->language('unit_test');

        if (count($results) === 0) {
            $results = $this->results;
        }

        $retval = [];
        foreach ($results as $result) {
            $temp = [];
            foreach ($result as $key => $val) {
                // Filter by visible items
                if (!in_array($key, $this->_test_items_visible)) {
                    continue;
                }

                // Translate specific values
                if (in_array($key, ['test_name', 'test_datatype', 'res_datatype', 'result'], true)) {
                    if (false !== ($line = kodhe()->lang->line(strtolower('ut_' . $val), false))) {
                        $val = $line;
                    }
                }

                // Translate keys
                $temp[kodhe()->lang->line('ut_' . $key, false)] = $val;
            }

            $retval[] = $temp;
        }

        return $retval;
    }

    // --------------------------------------------------------------------

    /**
     * Set the template
     *
     * This lets us set the template to be used to display results
     *
     * @param string $template Template string
     * @return void
     */
    public function set_template($template)
    {
        $this->_template = $template;
        $this->_template_rows = null;

        // Also update reporter template
        $this->getReporter()->setTemplate($template);
    }

    // --------------------------------------------------------------------

    /**
     * Set visible test items
     *
     * @param array $items Items to display in results
     * @return void
     */
    public function set_test_items($items)
    {
        if (!empty($items) && is_array($items)) {
            $this->_test_items_visible = $items;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Reset all test results
     *
     * @return void
     */
    public function reset(): void
    {
        $this->results = [];
        if ($this->collection !== null) {
            $this->collection->clear();
        }
    }

    // --------------------------------------------------------------------

    /**
     * Generate a backtrace
     *
     * This lets us show file names and line numbers
     *
     * @return array
     */
    protected function _backtrace()
    {
        $back = debug_backtrace();
        return [
            'file' => isset($back[1]['file']) ? $back[1]['file'] : '',
            'line' => isset($back[1]['line']) ? $back[1]['line'] : ''
        ];
    }

    // --------------------------------------------------------------------

    /**
     * Get Default Template
     *
     * @return void
     */
    protected function _default_template()
    {
        $this->_template = "\n" . '<table style="width:100%; font-size:small; margin:10px 0; border-collapse:collapse; border:1px solid #CCC;">{rows}' . "\n</table>";

        $this->_template_rows = "\n\t<tr>\n\t\t" . '<th style="text-align: left; border-bottom:1px solid #CCC;">{item}</th>'
            . "\n\t\t" . '<td style="border-bottom:1px solid #CCC;">{result}</td>' . "\n\t</tr>";
    }

    // --------------------------------------------------------------------

    /**
     * Parse Template
     *
     * Harvests the data within the template {pseudo-variables}
     *
     * @return void
     */
    protected function _parse_template()
    {
        if ($this->_template_rows !== null) {
            return;
        }

        if ($this->_template === null || !preg_match('/\{rows\}(.*?)\{\/rows\}/si', $this->_template, $match)) {
            $this->_default_template();
            return;
        }

        $this->_template_rows = $match[1];
        $this->_template = str_replace($match[0], '{rows}', $this->_template);
    }
}

// Helper function to test boolean TRUE
if (!function_exists('Kodhe\\Test\\is_true')) {
    /**
     * Helper function to test boolean TRUE
     *
     * @param mixed $test Value to test
     * @return bool       Whether value is strictly TRUE
     */
    function is_true($test)
    {
        return ($test === true);
    }
}

// Helper function to test boolean FALSE
if (!function_exists('Kodhe\\Test\\is_false')) {
    /**
     * Helper function to test boolean FALSE
     *
     * @param mixed $test Value to test
     * @return bool       Whether value is strictly FALSE
     */
    function is_false($test)
    {
        return ($test === false);
    }
}
