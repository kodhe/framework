<?php

namespace Kodhe\Calendar\Tests;

use Kodhe\Calendar\Calendar;
use Kodhe\Calendar\Renderers\JsonRenderer;
use PHPUnit\Framework\TestCase;

/**
 * Class CalendarTest
 *
 * Unit tests for main Calendar class
 *
 * @package     Kodhe\Calendar\Tests
 * @covers      \Kodhe\Calendar\Calendar
 */
class CalendarTest extends TestCase
{
    /**
     * @var Calendar
     */
    private $calendar;

    protected function setUp(): void
    {
        $this->calendar = new Calendar();
    }

    public function testGenerateReturnsHtmlString(): void
    {
        $html = $this->calendar->generate(2026, 8);

        $this->assertIsString($html);
        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('</table>', $html);
    }

    public function testGenerateUsesCurrentDateWhenNoParametersProvided(): void
    {
        $html = $this->calendar->generate();

        $this->assertIsString($html);
        $currentYear = date('Y');
        $this->assertStringContainsString((string) $currentYear, $html);
    }

    public function testGetMonthNameReturnsCorrectName(): void
    {
        $this->calendar->initialize(['locale' => 'en']);
        $this->assertEquals('August', $this->calendar->getMonthName(8));
    }

    public function testGetMonthNameReturnsIndonesianName(): void
    {
        $this->calendar->initialize(['locale' => 'id']);
        $this->assertEquals('Agustus', $this->calendar->getMonthName(8));
    }

    public function testGetDayNamesReturnsSevenDays(): void
    {
        $days = $this->calendar->getDayNames('abr');
        $this->assertCount(7, $days);
    }

    public function testGetDayNamesReturnsEnglishAbr(): void
    {
        $this->calendar->initialize(['locale' => 'en']);
        $days = $this->calendar->getDayNames('abr');
        $this->assertEquals(['S', 'M', 'T', 'W', 'T', 'F', 'S'], $days);
    }

    public function testAdjustDateDelegatesToGenerator(): void
    {
        [$year, $month] = $this->calendar->adjustDate(13, 2026);
        $this->assertEquals(2027, $year);
        $this->assertEquals(1, $month);
    }

    public function testGetTotalDaysReturnsCorrectCount(): void
    {
        $this->assertEquals(31, $this->calendar->getTotalDays(8, 2026));
        $this->assertEquals(28, $this->calendar->getTotalDays(2, 2026));
        $this->assertEquals(29, $this->calendar->getTotalDays(2, 2028)); // Leap year
    }

    public function testGetTotalWeeksReturnsCorrectCount(): void
    {
        $weeks = $this->calendar->getTotalWeeks(8, 2026);
        $this->assertGreaterThanOrEqual(4, $weeks);
        $this->assertLessThanOrEqual(6, $weeks);
    }

    public function testInitializeMergesConfig(): void
    {
        $this->calendar->initialize(['start_day' => 'monday', 'locale' => 'id']);

        $config = $this->calendar->getConfig();
        $this->assertEquals('monday', $config['start_day']);
        $this->assertEquals('id', $config['locale']);
    }

    public function testSetRendererAcceptsCustomRenderer(): void
    {
        $jsonRenderer = new JsonRenderer();
        $result = $this->calendar->setRenderer($jsonRenderer);

        $this->assertSame($this->calendar, $result);
    }

    public function testAsJsonReturnsJsonString(): void
    {
        $json = $this->calendar->asJson(2026, 8, [
            15 => 'Event 1',
            22 => 'Event 2',
        ]);

        $this->assertIsString($json);
        $data = json_decode($json, true);

        $this->assertNotNull($data);
        $this->assertArrayHasKey('year', $data);
        $this->assertArrayHasKey('month', $data);
        $this->assertArrayHasKey('events', $data);
        $this->assertEquals(2026, $data['year']);
        $this->assertEquals(8, $data['month']);
    }

    public function testDefaultTemplateReturnsArray(): void
    {
        $template = $this->calendar->defaultTemplate();

        $this->assertIsArray($template);
        $this->assertArrayHasKey('table_open', $template);
        $this->assertArrayHasKey('cal_row_start', $template);
        $this->assertArrayHasKey('cal_row_end', $template);
    }

    public function testGenerateWithDataMarksEvents(): void
    {
        $html = $this->calendar->generate(2026, 8, [
            15 => 'http://example.com/event/15',
        ]);

        $this->assertStringContainsString('http://example.com/event/15', $html);
    }

    public function testGenerateWithArrayDataMarksEventsWithTitle(): void
    {
        $html = $this->calendar->generate(2026, 8, [
            15 => ['url' => 'http://example.com/event/15', 'title' => 'Important Event'],
        ]);

        $this->assertStringContainsString('http://example.com/event/15', $html);
        $this->assertStringContainsString('Important Event', $html);
    }

    public function testGetGeneratorReturnsMonthGeneratorInstance(): void
    {
        $generator = $this->calendar->getGenerator();
        $this->assertInstanceOf(\Kodhe\Calendar\Generators\MonthGenerator::class, $generator);
    }

    public function testGetLexiconRepositoryReturnsInstance(): void
    {
        $repository = $this->calendar->getLexiconRepository();
        $this->assertInstanceOf(\Kodhe\Calendar\Localization\LexiconRepository::class, $repository);
    }

    public function testGenerateWithMondayStartDay(): void
    {
        $this->calendar->initialize(['start_day' => 'monday']);
        $html = $this->calendar->generate(2026, 8);

        $this->assertIsString($html);
        $this->assertStringContainsString('<table', $html);
    }

    public function testGenerateCachesResults(): void
    {
        // Generate twice with same parameters
        $first = $this->calendar->generate(2026, 8);
        $second = $this->calendar->generate(2026, 8);

        // Should be identical (cached in generator)
        $this->assertEquals($first, $second);
    }
}
