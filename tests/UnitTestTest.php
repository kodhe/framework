<?php

declare(strict_types=1);

namespace Kodhe\Framework\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the UnitTest class
 */
class UnitTestTest extends TestCase
{
    /**
     * @var \Kodhe\Framework\Test\UnitTest
     */
    private $unit;

    protected function setUp(): void
    {
        parent::setUp();
        // Include the mock kodhe() function if not already defined
        if (!function_exists('Kodhe\Framework\Test\\kodhe')) {
            // The UnitTest class depends on kodhe() function and legacy classes
            // We need to provide mocks for these dependencies
        }
        $this->unit = new \Kodhe\Framework\Test\UnitTest();
    }

    /**
     * Test that UnitTest can be instantiated
     */
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(\Kodhe\Framework\Test\UnitTest::class, $this->unit);
    }

    /**
     * Test active property defaults to TRUE
     */
    public function testActivePropertyDefaultsToTrue(): void
    {
        $this->assertTrue($this->unit->active);
    }

    /**
     * Test strict property defaults to FALSE
     */
    public function testStrictPropertyDefaultsToFalse(): void
    {
        $this->assertFalse($this->unit->strict);
    }

    /**
     * Test results property is an empty array initially
     */
    public function testResultsPropertyIsEmptyArray(): void
    {
        $this->assertIsArray($this->unit->results);
        $this->assertEmpty($this->unit->results);
    }

    /**
     * Test run method with boolean true comparison
     */
    public function testRunWithBooleanTrue(): void
    {
        $result = $this->unit->run(true, true, 'Test boolean true');
        
        // The run method returns a report string
        $this->assertIsString($result);
        
        // Check that a result was recorded
        $this->assertCount(1, $this->unit->results);
        $this->assertEquals('passed', $this->unit->results[0]['result']);
    }

    /**
     * Test run method with boolean false comparison
     */
    public function testRunWithBooleanFalse(): void
    {
        $result = $this->unit->run(false, false, 'Test boolean false');
        
        $this->assertIsString($result);
        $this->assertCount(1, $this->unit->results);
        $this->assertEquals('passed', $this->unit->results[0]['result']);
    }

    /**
     * Test run method with failed comparison
     */
    public function testRunWithFailedComparison(): void
    {
        $result = $this->unit->run(true, false, 'Test failed comparison');
        
        $this->assertIsString($result);
        $this->assertCount(1, $this->unit->results);
        $this->assertEquals('failed', $this->unit->results[0]['result']);
    }

    /**
     * Test run method with string comparison
     */
    public function testRunWithStringComparison(): void
    {
        $result = $this->unit->run('hello', 'hello', 'Test string equality');
        
        $this->assertIsString($result);
        $this->assertCount(1, $this->unit->results);
        $this->assertEquals('passed', $this->unit->results[0]['result']);
    }

    /**
     * Test run method with integer comparison
     */
    public function testRunWithIntegerComparison(): void
    {
        $result = $this->unit->run(42, 42, 'Test integer equality');
        
        $this->assertIsString($result);
        $this->assertCount(1, $this->unit->results);
        $this->assertEquals('passed', $this->unit->results[0]['result']);
    }

    /**
     * Test run method with array comparison
     */
    public function testRunWithArrayComparison(): void
    {
        $testArray = ['key' => 'value'];
        $result = $this->unit->run($testArray, $testArray, 'Test array equality');
        
        $this->assertIsString($result);
        $this->assertCount(1, $this->unit->results);
        $this->assertEquals('passed', $this->unit->results[0]['result']);
    }

    /**
     * Test run method with type checking functions
     */
    public function testRunWithTypeCheckingFunctions(): void
    {
        // Test is_string
        $result = $this->unit->run('hello', 'is_string', 'Test is_string');
        $this->assertEquals('passed', $this->unit->results[0]['result']);

        // Test is_int
        $result = $this->unit->run(42, 'is_int', 'Test is_int');
        $this->assertEquals('passed', $this->unit->results[1]['result']);

        // Test is_array
        $result = $this->unit->run([], 'is_array', 'Test is_array');
        $this->assertEquals('passed', $this->unit->results[2]['result']);

        // Test is_bool
        $result = $this->unit->run(true, 'is_bool', 'Test is_bool');
        $this->assertEquals('passed', $this->unit->results[3]['result']);

        // Test is_numeric
        $result = $this->unit->run(123, 'is_numeric', 'Test is_numeric');
        $this->assertEquals('passed', $this->unit->results[4]['result']);

        // Test is_null
        $result = $this->unit->run(null, 'is_null', 'Test is_null');
        $this->assertEquals('passed', $this->unit->results[5]['result']);
    }

    /**
     * Test use_strict method
     */
    public function testUseStrictMethod(): void
    {
        $this->unit->use_strict(true);
        $this->assertTrue($this->unit->strict);

        $this->unit->use_strict(false);
        $this->assertFalse($this->unit->strict);
    }

    /**
     * Test active method to enable/disable testing
     */
    public function testActiveMethod(): void
    {
        $this->unit->active(false);
        $this->assertFalse($this->unit->active);

        $this->unit->active(true);
        $this->assertTrue($this->unit->active);
    }

    /**
     * Test run method returns FALSE when active is FALSE
     */
    public function testRunReturnsFalseWhenNotActive(): void
    {
        $this->unit->active(false);
        $result = $this->unit->run(true, true, 'Test inactive');
        
        $this->assertFalse($result);
        $this->assertEmpty($this->unit->results);
    }

    /**
     * Test set_test_items method
     */
    public function testSetTestItemsMethod(): void
    {
        $items = ['test_name', 'result'];
        $this->unit->set_test_items($items);
        
        // Access the protected property via reflection
        $reflection = new \ReflectionClass($this->unit);
        $property = $reflection->getProperty('_test_items_visible');
        $property->setAccessible(true);
        
        $this->assertEquals($items, $property->getValue($this->unit));
    }

    /**
     * Test result method returns array of results
     */
    public function testResultMethod(): void
    {
        $this->unit->run(true, true, 'Test 1');
        $this->unit->run(false, false, 'Test 2');
        
        $results = $this->unit->result();
        
        $this->assertIsArray($results);
        $this->assertCount(2, $results);
    }

    /**
     * Test report method with empty results
     */
    public function testReportMethodWithEmptyResults(): void
    {
        $report = $this->unit->report([]);
        
        $this->assertIsString($report);
    }

    /**
     * Test set_template method
     */
    public function testSetTemplateMethod(): void
    {
        $template = '<table>{rows}</table>';
        $this->unit->set_template($template);
        
        // Access the protected property via reflection
        $reflection = new \ReflectionClass($this->unit);
        $property = $reflection->getProperty('_template');
        $property->setAccessible(true);
        
        $this->assertEquals($template, $property->getValue($this->unit));
    }

    /**
     * Test multiple runs accumulate results
     */
    public function testMultipleRunsAccumulateResults(): void
    {
        $this->unit->run(true, true, 'Test 1');
        $this->unit->run(true, true, 'Test 2');
        $this->unit->run(true, true, 'Test 3');
        
        $this->assertCount(3, $this->unit->results);
    }

    /**
     * Test run method records file and line information
     */
    public function testRunRecordsFileAndLine(): void
    {
        $this->unit->run(true, true, 'Test backtrace');
        
        $this->assertArrayHasKey('file', $this->unit->results[0]);
        $this->assertArrayHasKey('line', $this->unit->results[0]);
    }

    /**
     * Test run method records notes
     */
    public function testRunRecordsNotes(): void
    {
        $notes = 'This is a test note';
        $this->unit->run(true, true, 'Test with notes', $notes);
        
        $this->assertEquals($notes, $this->unit->results[0]['notes']);
    }

    /**
     * Test is_true helper function
     */
    public function testIsTrueHelperFunction(): void
    {
        $this->assertTrue(\Kodhe\Framework\Test\is_true(true));
        $this->assertFalse(\Kodhe\Framework\Test\is_true(false));
        $this->assertFalse(\Kodhe\Framework\Test\is_true(1));
        $this->assertFalse(\Kodhe\Framework\Test\is_true('true'));
    }

    /**
     * Test is_false helper function
     */
    public function testIsFalseHelperFunction(): void
    {
        $this->assertTrue(\Kodhe\Framework\Test\is_false(false));
        $this->assertFalse(\Kodhe\Framework\Test\is_false(true));
        $this->assertFalse(\Kodhe\Framework\Test\is_false(0));
        $this->assertFalse(\Kodhe\Framework\Test\is_false('false'));
    }

    /**
     * Test run with is_float type check
     */
    public function testRunWithIsFloat(): void
    {
        $result = $this->unit->run(3.14, 'is_float', 'Test is_float');
        $this->assertEquals('passed', $this->unit->results[0]['result']);
    }

    /**
     * Test run with is_double type check (alias of is_float)
     */
    public function testRunWithIsDouble(): void
    {
        $result = $this->unit->run(2.71, 'is_double', 'Test is_double');
        $this->assertEquals('passed', $this->unit->results[0]['result']);
    }

    /**
     * Test run with is_resource type check
     */
    public function testRunWithIsResource(): void
    {
        $fp = fopen('php://memory', 'r');
        $result = $this->unit->run($fp, 'is_resource', 'Test is_resource');
        $this->assertEquals('passed', $this->unit->results[0]['result']);
        fclose($fp);
    }

    /**
     * Test strict comparison catches type differences
     */
    public function testStrictComparisonCatchesTypeDifferences(): void
    {
        $this->unit->use_strict(true);
        
        // With strict comparison, "1" should not equal 1
        $result = $this->unit->run("1", 1, 'Test strict comparison');
        
        $this->assertEquals('failed', $this->unit->results[0]['result']);
    }

    /**
     * Test loose comparison allows type juggling
     */
    public function testLooseComparisonAllowsTypeJuggling(): void
    {
        $this->unit->use_strict(false);
        
        // With loose comparison, "1" should equal 1
        $result = $this->unit->run("1", 1, 'Test loose comparison');
        
        $this->assertEquals('passed', $this->unit->results[0]['result']);
    }
}
