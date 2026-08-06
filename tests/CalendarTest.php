<?php

declare(strict_types=1);

namespace Kodhe\Tests;

use PHPUnit\Framework\TestCase;
use Kodhe\Calendar\Calendar;

/**
 * Unit tests for the Calendar library
 */
class CalendarTest extends TestCase
{
    /**
     * @var Calendar
     */
    private $calendar;

    protected function setUp(): void
    {
        parent::setUp();
        // Include helpers that provide kodhe() mock
        require_once __DIR__ . '/../../calendar/src/helpers.php';
        $this->calendar = new Calendar();
    }

    /**
     * Test that Calendar can be instantiated
     */
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(Calendar::class, $this->calendar);
    }

    /**
     * Test generate method returns a calendar table
     */
    public function testGenerateReturnsCalendarTable(): void
    {
        $result = $this->calendar->generate(2024, 1);
        
        $this->assertIsString($result);
        $this->assertStringContainsString('<table', $result);
        $this->assertStringContainsString('</table>', $result);
    }

    /**
     * Test generate method with current year and month
     */
    public function testGenerateWithCurrentDate(): void
    {
        $result = $this->calendar->generate();
        
        $this->assertIsString($result);
        $this->assertStringContainsString('<table', $result);
    }

    /**
     * Test initialize method sets preferences
     */
    public function testInitializeMethod(): void
    {
        $prefs = [
            'show_next_prev' => true,
            'next_prev_url' => 'http://example.com/calendar'
        ];
        
        $this->calendar->initialize($prefs);
        
        // Access property via reflection to verify
        $reflection = new \ReflectionClass($this->calendar);
        $property = $reflection->getProperty('prefs');
        $property->setAccessible(true);
        
        $actualPrefs = $property->getValue($this->calendar);
        $this->assertTrue($actualPrefs['show_next_prev']);
        $this->assertEquals('http://example.com/calendar', $actualPrefs['next_prev_url']);
    }

    /**
     * Test get_month_name method
     */
    public function testGetMonthName(): void
    {
        $monthName = $this->calendar->get_month_name(1);
        $this->assertIsString($monthName);
        $this->assertNotEmpty($monthName);
    }

    /**
     * Test get_day_name method
     */
    public function testGetDayName(): void
    {
        $dayName = $this->calendar->get_day_name(0);
        $this->assertIsString($dayName);
        $this->assertNotEmpty($dayName);
    }

    /**
     * Test days_in_month helper function
     */
    public function testDaysInMonthHelper(): void
    {
        // January has 31 days
        $this->assertEquals(31, \Kodhe\Tests\days_in_month(1, 2024));
        
        // February in leap year has 29 days
        $this->assertEquals(29, \Kodhe\Tests\days_in_month(2, 2024));
        
        // February in non-leap year has 28 days
        $this->assertEquals(28, \Kodhe\Tests\days_in_month(2, 2023));
        
        // April has 30 days
        $this->assertEquals(30, \Kodhe\Tests\days_in_month(4, 2024));
        
        // Invalid month returns 0
        $this->assertEquals(0, \Kodhe\Tests\days_in_month(13, 2024));
        $this->assertEquals(0, \Kodhe\Tests\days_in_month(0, 2024));
    }

    /**
     * Test leap year calculation
     */
    public function testLeapYearCalculation(): void
    {
        // 2024 is a leap year
        $this->assertEquals(29, \Kodhe\Tests\days_in_month(2, 2024));
        
        // 2020 is a leap year
        $this->assertEquals(29, \Kodhe\Tests\days_in_month(2, 2020));
        
        // 2000 is a leap year (divisible by 400)
        $this->assertEquals(29, \Kodhe\Tests\days_in_month(2, 2000));
        
        // 1900 is not a leap year (divisible by 100 but not 400)
        $this->assertEquals(28, \Kodhe\Tests\days_in_month(2, 1900));
        
        // 2023 is not a leap year
        $this->assertEquals(28, \Kodhe\Tests\days_in_month(2, 2023));
    }

    /**
     * Test all months have correct number of days
     */
    public function testAllMonthsHaveCorrectDays(): void
    {
        $expectedDays = [
            1 => 31,  // January
            2 => 28,  // February (non-leap year)
            3 => 31,  // March
            4 => 30,  // April
            5 => 31,  // May
            6 => 30,  // June
            7 => 31,  // July
            8 => 31,  // August
            9 => 30,  // September
            10 => 31, // October
            11 => 30, // November
            12 => 31  // December
        ];

        foreach ($expectedDays as $month => $days) {
            $this->assertEquals($days, \Kodhe\Tests\days_in_month($month, 2023), "Month {$month} should have {$days} days");
        }
    }

    /**
     * Test generate with custom template
     */
    public function testGenerateWithCustomPreferences(): void
    {
        $prefs = [
            'template' => [
                'heading_row_start' => '<tr class="custom-heading">',
                'heading_title_element' => 'h3'
            ]
        ];
        
        $this->calendar->initialize($prefs);
        $result = $this->calendar->generate(2024, 1);
        
        $this->assertIsString($result);
    }

    /**
     * Test that generated calendar contains year
     */
    public function testGeneratedCalendarContainsYear(): void
    {
        $result = $this->calendar->generate(2024, 6);
        
        $this->assertStringContainsString('2024', $result);
    }

    /**
     * Test that generated calendar contains month name
     */
    public function testGeneratedCalendarContainsMonthName(): void
    {
        $result = $this->calendar->generate(2024, 6);
        
        // June should be in the output
        $this->assertStringContainsString('June', $result);
    }

    /**
     * Test that generated calendar contains day names
     */
    public function testGeneratedCalendarContainsDayNames(): void
    {
        $result = $this->calendar->generate(2024, 1);
        
        // Should contain day names
        $this->assertStringContainsString('Sun', $result);
        $this->assertStringContainsString('Mon', $result);
    }

    /**
     * Test multiple generate calls work correctly
     */
    public function testMultipleGenerateCalls(): void
    {
        $result1 = $this->calendar->generate(2024, 1);
        $result2 = $this->calendar->generate(2024, 12);
        
        $this->assertIsString($result1);
        $this->assertIsString($result2);
        $this->assertStringContainsString('January', $result1);
        $this->assertStringContainsString('December', $result2);
    }
}
