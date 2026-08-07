<?php

namespace Kodhe\Calendar;

use Kodhe\Calendar\Contracts\CalendarInterface;
use Kodhe\Calendar\Contracts\CalendarRendererInterface;
use Kodhe\Calendar\Generators\MonthGenerator;
use Kodhe\Calendar\Localization\LexiconRepository;
use Kodhe\Calendar\Renderers\HtmlTableRenderer;

/**
 * Class Calendar
 *
 * Main Calendar library for CodeIgniter 3
 * Provides backward compatible API while using modern PSR-4 structure
 *
 * @package     Kodhe\Calendar
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class Calendar implements CalendarInterface
{
    /**
     * Month generator instance
     *
     * @var MonthGenerator
     */
    private $generator;

    /**
     * Renderer instance
     *
     * @var CalendarRendererInterface
     */
    private $renderer;

    /**
     * Lexicon repository for localized names
     *
     * @var LexiconRepository
     */
    private $lexiconRepository;

    /**
     * Configuration array
     *
     * @var array
     */
    private $config = [];

    /**
     * Constructor
     *
     * @param array $config Configuration options
     */
    public function __construct(array $config = [])
    {
        $this->lexiconRepository = new LexiconRepository();
        $this->generator         = new MonthGenerator();
        $this->renderer          = new HtmlTableRenderer($this->lexiconRepository);
        $this->config            = $config;

        // Store current year/month for "today" detection
        $this->config['_year']  = (int) date('Y');
        $this->config['_month'] = (int) date('n');
    }

    /**
     * Generate calendar output
     *
     * @param int|string $year  Year number
     * @param int|string $month Month number (1-12)
     * @param array      $data  Associative array of data to display on specific days
     * @return string           Rendered calendar output
     */
    public function generate($year = '', $month = '', $data = []): string
    {
        // Use current year/month if not specified
        if (empty($year)) {
            $year = date('Y');
        }
        if (empty($month)) {
            $month = date('n');
        }

        // Build calendar structure
        $structure = $this->generator->build($year, $month, $this->config);

        // Add config to data context
        $renderConfig = array_merge($this->config, $structure);

        // Render with configured renderer
        return $this->renderer->render($structure, $data, $renderConfig);
    }

    /**
     * Set custom renderer
     *
     * @param CalendarRendererInterface $renderer
     * @return self
     */
    public function setRenderer(CalendarRendererInterface $renderer): self
    {
        $this->renderer = $renderer;
        return $this;
    }

    /**
     * Get default template array
     *
     * @return array
     */
    public function defaultTemplate(): array
    {
        return $this->renderer->defaultTemplate();
    }

    /**
     * Parse custom template
     *
     * @param string $template Template string
     * @return array           Parsed template array
     */
    public function parseTemplate(string $template): array
    {
        // Simple parser - in real implementation would parse CI3 template format
        $parsed = [];
        preg_match_all('/(\{tag_open\})(.*?)(\{tag_close\})/s', $template, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $key = $match[1] ?? '';
            $value = $match[2] ?? '';
            $parsed[$key] = $value;
        }

        return $parsed ?: ['template' => $template];
    }

    /**
     * Get month name by month number
     *
     * @param int $month Month number (1-12)
     * @return string   Month name
     */
    public function getMonthName(int $month): string
    {
        $locale  = $this->config['locale'] ?? 'en';
        $type    = $this->config['month_type'] ?? 'long';
        return $this->lexiconRepository->monthName($month, $locale, $type);
    }

    /**
     * Get day names array
     *
     * @param string $dayType 'long', 'short', or 'abr'
     * @return array          Array of day names
     */
    public function getDayNames(string $dayType = 'abr'): array
    {
        $locale = $this->config['locale'] ?? 'en';
        return $this->lexiconRepository->dayNames($dayType, $locale);
    }

    /**
     * Adjust date for overflow/underflow months
     *
     * @param int $month Month number
     * @param int $year  Year number
     * @return array     [year, month]
     */
    public function adjustDate(int $month, int $year): array
    {
        return $this->generator->adjustDate($month, $year);
    }

    /**
     * Get total days in a month
     *
     * @param int $month Month number
     * @param int $year  Year number
     * @return int      Total days
     */
    public function getTotalDays(int $month, int $year): int
    {
        return $this->generator->totalDays($month, $year);
    }

    /**
     * Get last day of week for a given date
     *
     * @param int $month Month number
     * @param int $year  Year number
     * @return int      Last day number
     */
    public function getLastDay(int $month, int $year): int
    {
        $startDay = $this->config['start_day'] ?? 'sunday';
        return $this->generator->getStartDay($month, $year, $startDay);
    }

    /**
     * Get total weeks in a month
     *
     * @param int    $month Month number
     * @param int    $year  Year number
     * @param string $days  Day type (not used, kept for BC)
     * @return int         Total weeks
     */
    public function getTotalWeeks(int $month, int $year, string $days = 'long'): int
    {
        $structure = $this->generator->build($year, $month, $this->config);
        return count($structure['weeks']);
    }

    /**
     * Initialize calendar with configuration
     *
     * @param array $config Configuration options
     * @return self
     */
    public function initialize(array $config = []): self
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * Get generator instance
     *
     * @return MonthGenerator
     */
    public function getGenerator(): MonthGenerator
    {
        return $this->generator;
    }

    /**
     * Get lexicon repository instance
     *
     * @return LexiconRepository
     */
    public function getLexiconRepository(): LexiconRepository
    {
        return $this->lexiconRepository;
    }

    /**
     * Get current configuration
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Generate calendar as JSON
     *
     * @param int|string $year
     * @param int|string $month
     * @param array      $data
     * @return string    JSON output
     */
    public function asJson($year = '', $month = '', $data = []): string
    {
        $originalRenderer = $this->renderer;
        $this->renderer   = new Renderers\JsonRenderer();
        $result           = $this->generate($year, $month, $data);
        $this->renderer   = $originalRenderer;

        return $result;
    }
}
