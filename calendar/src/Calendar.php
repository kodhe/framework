<?php

declare(strict_types=0);

namespace Kodhe\Framework\Calendar;

use Kodhe\Framework\Calendar\Contracts\CalendarInterface;
use Kodhe\Framework\Calendar\Contracts\CalendarRendererInterface;
use Kodhe\Framework\Calendar\Generators\MonthGenerator;
use Kodhe\Framework\Calendar\Renderers\HtmlTableRenderer;
use Kodhe\Framework\Calendar\Renderers\JsonRenderer;
use Kodhe\Framework\Calendar\Localization\LexiconRepository;
use Kodhe\Framework\Calendar\Traits\ConfigurableTrait;

/**
 * Calendar Library for CodeIgniter 3
 *
 * Generate calendars with support for custom templates,
 * event marking, and multi-locale day/month names
 *
 * @package     Kodhe\Calendar
 * @author      EllisLab Dev Team (original), Refactored by Kodhe
 * @version     2.0.0
 * @license     MIT
 * @link        https://github.com/kodhe/calendar
 */
class Calendar implements CalendarInterface
{
    use ConfigurableTrait;

    /**
     * Month generator
     *
     * @var MonthGenerator
     */
    private $monthGenerator;

    /**
     * Calendar renderer
     *
     * @var CalendarRendererInterface
     */
    private $renderer;

    /**
     * Lexicon repository
     *
     * @var LexiconRepository
     */
    private $lexiconRepository;

    // --------------------------------------------------------------------

    /**
     * CI Singleton (backward compatibility)
     *
     * @var object|null
     */
    protected $CI;

    // --------------------------------------------------------------------

    /**
     * Class constructor
     *
     * Loads the calendar language file and sets the default time reference
     *
     * @param array $config Calendar options
     * @return void
     */
    public function __construct($config = [])
    {
        // Backward compatibility with CI3
        if (function_exists('kodhe')) {
            $this->CI = kodhe();
            if ($this->CI && method_exists($this->CI->lang, 'load')) {
                $this->CI->lang->load('calendar');
            }
        }

        $this->monthGenerator = new MonthGenerator();
        $this->renderer = new HtmlTableRenderer();
        $this->lexiconRepository = LexiconRepository::getInstance();

        empty($config) OR $this->initialize($config);

        if (function_exists('log_message')) {
            log_message('info', 'Calendar Class Initialized');
        }
    }

    // --------------------------------------------------------------------

    /**
     * Set renderer
     *
     * @param CalendarRendererInterface $renderer
     * @return self
     */
    public function setRenderer(CalendarRendererInterface $renderer): self
    {
        $this->renderer = $renderer;
        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Generate the calendar
     *
     * @param int|string $year
     * @param int|string $month
     * @param array $data
     * @return string
     */
    public function generate($year = '', $month = '', $data = []): string
    {
        $structure = $this->monthGenerator->build($year, $month, $this->getAllConfig());
        return $this->renderer->render($structure, $data, $this->getAllConfig());
    }

    /**
     * Generate calendar as JSON
     *
     * @param int|string $year
     * @param int|string $month
     * @param array $data
     * @return string
     */
    public function asJson($year = '', $month = '', $data = []): string
    {
        $originalRenderer = $this->renderer;
        $this->renderer = new JsonRenderer();
        $result = $this->generate($year, $month, $data);
        $this->renderer = $originalRenderer;
        return $result;
    }

    // --------------------------------------------------------------------

    /**
     * Get month name (backward compatible alias)
     *
     * @param int $month
     * @return string
     */
    public function get_month_name($month): string
    {
        return $this->getMonthName((int)$month);
    }

    /**
     * Get month name
     *
     * @param int $month
     * @return string
     */
    public function getMonthName(int $month): string
    {
        $type = $this->getConfig('month_type', 'long');
        $locale = $this->getConfig('locale', 'en');
        return $this->lexiconRepository->monthName($month, $locale, $type);
    }

    // --------------------------------------------------------------------

    /**
     * Get day names (backward compatible alias)
     *
     * @param string $dayType
     * @return array
     */
    public function get_day_names($dayType = 'abr'): array
    {
        return $this->getDayNames((string)$dayType);
    }

    /**
     * Get day names
     *
     * @param string $dayType
     * @return array
     */
    public function getDayNames(string $dayType = 'abr'): array
    {
        $locale = $this->getConfig('locale', 'en');
        return $this->lexiconRepository->dayNames($dayType, $locale);
    }

    // --------------------------------------------------------------------

    /**
     * Adjust date (backward compatible alias)
     *
     * @param int $month
     * @param int $year
     * @return array
     */
    public function adjust_date($month, $year): array
    {
        return $this->adjustDate((int)$month, (int)$year);
    }

    /**
     * Adjust date
     *
     * @param int $month
     * @param int $year
     * @return array
     */
    public function adjustDate(int $month, int $year): array
    {
        [$year, $month] = $this->monthGenerator->adjustDate($month, $year);
        return ['month' => sprintf('%02d', $month), 'year' => $year];
    }

    // --------------------------------------------------------------------

    /**
     * Get total days (backward compatible alias)
     *
     * @param int $month
     * @param int $year
     * @return int
     */
    public function get_total_days($month, $year): int
    {
        return $this->getTotalDays((int)$month, (int)$year);
    }

    /**
     * Get total days in a month
     *
     * @param int $month
     * @param int $year
     * @return int
     */
    public function getTotalDays(int $month, int $year): int
    {
        return $this->monthGenerator->totalDays($month, $year);
    }

    // --------------------------------------------------------------------

    /**
     * Get last day of month (backward compatible)
     *
     * @param int $month
     * @param int $year
     * @return int
     */
    public function get_last_day($month, $year): int
    {
        return $this->getLastDay((int)$month, (int)$year);
    }

    /**
     * Get last day of month
     *
     * @param int $month
     * @param int $year
     * @return int
     */
    public function getLastDay(int $month, int $year): int
    {
        return $this->getTotalDays($month, $year);
    }

    // --------------------------------------------------------------------

    /**
     * Get total weeks (backward compatible)
     *
     * @param int $month
     * @param int $year
     * @param array|null $days
     * @return int
     */
    public function get_total_weeks($month, $year, $days = null): int
    {
        return $this->getTotalWeeks((int)$month, (int)$year, $days);
    }

    /**
     * Get total weeks
     *
     * @param int $month
     * @param int $year
     * @param array|null $days
     * @return int
     */
    public function getTotalWeeks(int $month, int $year, ?array $days = null): int
    {
        $totalDays = $this->getTotalDays($month, $year);
        $startDay = $this->monthGenerator->getStartDay($month, $year, $this->getConfig('start_day', 'sunday'));
        
        $weeks = (int) ceil(($totalDays + abs($startDay - 1)) / 7);
        return max($weeks, 1);
    }

    // --------------------------------------------------------------------

    /**
     * Default template (backward compatible)
     *
     * @return array
     */
    public function default_template(): array
    {
        return $this->renderer->defaultTemplate();
    }

    // --------------------------------------------------------------------

    /**
     * Parse template (backward compatible)
     *
     * @return self
     */
    public function parse_template(): self
    {
        // Template is now handled by renderer
        return $this;
    }
}
