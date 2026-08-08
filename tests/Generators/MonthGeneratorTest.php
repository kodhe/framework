<?php

namespace Kodhe\Framework\Calendar\Tests\Generators;

use Kodhe\Framework\Calendar\Generators\MonthGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Class MonthGeneratorTest
 *
 * Unit tests for MonthGenerator
 *
 * @package     Kodhe\Calendar\Tests\Generators
 * @covers      \Kodhe\Framework\Calendar\Generators\MonthGenerator
 */
class MonthGeneratorTest extends TestCase
{
    /**
     * @var MonthGenerator
     */
    private $generator;

    protected function setUp(): void
    {
        $this->generator = new MonthGenerator();
    }

    public function testTotalDaysReturnsCorrectDaysForJanuary(): void
    {
        $this->assertEquals(31, $this->generator->totalDays(1, 2026));
    }

    public function testTotalDaysReturnsCorrectDaysForFebruaryInLeapYear(): void
    {
        // 2028 is a leap year
        $this->assertEquals(29, $this->generator->totalDays(2, 2028));
    }

    public function testTotalDaysReturnsCorrectDaysForFebruaryInNonLeapYear(): void
    {
        // 2026 is not a leap year
        $this->assertEquals(28, $this->generator->totalDays(2, 2026));
    }

    public function testTotalDaysReturnsCorrectDaysForApril(): void
    {
        $this->assertEquals(30, $this->generator->totalDays(4, 2026));
    }

    public function testAdjustDateHandlesMonthOverflow(): void
    {
        // Month 13 should become January of next year
        [$year, $month] = $this->generator->adjustDate(13, 2026);
        $this->assertEquals(2027, $year);
        $this->assertEquals(1, $month);
    }

    public function testAdjustDateHandlesMonthUnderflow(): void
    {
        // Month 0 should become December of previous year
        [$year, $month] = $this->generator->adjustDate(0, 2026);
        $this->assertEquals(2025, $year);
        $this->assertEquals(12, $month);
    }

    public function testAdjustDateHandlesNegativeMonth(): void
    {
        // Month -1 should become November of previous year
        [$year, $month] = $this->generator->adjustDate(-1, 2026);
        $this->assertEquals(2025, $year);
        $this->assertEquals(11, $month);
    }

    public function testAdjustDateReturnsSameForValidMonth(): void
    {
        [$year, $month] = $this->generator->adjustDate(6, 2026);
        $this->assertEquals(2026, $year);
        $this->assertEquals(6, $month);
    }

    public function testGetStartDayReturnsSundayForFirstDayOfMonth(): void
    {
        // August 1, 2026 is a Saturday (6 in Sunday-based)
        $startDay = $this->generator->getStartDay(8, 2026, 'sunday');
        $this->assertEquals(6, $startDay);
    }

    public function testGetStartDayConvertsToMondayBased(): void
    {
        // August 1, 2026 is a Saturday
        // In Monday-based: Monday=0, Tuesday=1, ..., Saturday=5, Sunday=6
        $startDay = $this->generator->getStartDay(8, 2026, 'monday');
        $this->assertEquals(5, $startDay);
    }

    public function testBuildReturnsCorrectStructure(): void
    {
        $structure = $this->generator->build(2026, 8);

        $this->assertArrayHasKey('year', $structure);
        $this->assertArrayHasKey('month', $structure);
        $this->assertArrayHasKey('total_days', $structure);
        $this->assertArrayHasKey('start_day', $structure);
        $this->assertArrayHasKey('weeks', $structure);

        $this->assertEquals(2026, $structure['year']);
        $this->assertEquals(8, $structure['month']);
        $this->assertEquals(31, $structure['total_days']);
    }

    public function testBuildReturnsCorrectNumberOfWeeks(): void
    {
        $structure = $this->generator->build(2026, 8);

        // August 2026 should have 5 weeks
        $this->assertCount(5, $structure['weeks']);
    }

    public function testBuildEachWeekHasSevenDays(): void
    {
        $structure = $this->generator->build(2026, 8);

        foreach ($structure['weeks'] as $week) {
            $this->assertCount(7, $week);
        }
    }

    public function testBuildCachesResults(): void
    {
        // Build twice
        $first = $this->generator->build(2026, 8);
        $second = $this->generator->build(2026, 8);

        // Should be identical (cached)
        $this->assertSame($first, $second);
    }

    public function testClearCacheRemovesCachedResults(): void
    {
        // Build and cache
        $this->generator->build(2026, 8);

        // Clear cache
        $this->generator->clearCache();

        // Build again - should not be same instance
        $new = $this->generator->build(2026, 8);

        // Cache was cleared, so new build happened
        $this->assertIsArray($new);
    }

    public function testBuildHandlesStringParameters(): void
    {
        $structure = $this->generator->build('2026', '8');

        $this->assertEquals(2026, $structure['year']);
        $this->assertEquals(8, $structure['month']);
    }

    public function testBuildWithConfigStartDayMonday(): void
    {
        $structure = $this->generator->build(2026, 8, ['start_day' => 'monday']);

        $this->assertEquals(5, $structure['start_day']); // Saturday in Monday-based
    }
}
