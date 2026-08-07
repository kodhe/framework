<?php

namespace Kodhe\Calendar\Contracts;

/**
 * Interface CalendarInterface
 *
 * Main interface for Calendar library
 *
 * @package     Kodhe\Calendar
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
interface CalendarInterface
{
    /**
     * Generate calendar output
     *
     * @param int|string $year  Year number
     * @param int|string $month Month number (1-12)
     * @param array      $data  Associative array of data to display on specific days
     * @return string           Rendered calendar output
     */
    public function generate($year = '', $month = '', $data = []): string;

    /**
     * Set custom renderer
     *
     * @param CalendarRendererInterface $renderer
     * @return self
     */
    public function setRenderer(CalendarRendererInterface $renderer): self;

    /**
     * Get default template array
     *
     * @return array
     */
    public function defaultTemplate(): array;

    /**
     * Parse custom template
     *
     * @param string $template
     * @return array
     */
    public function parseTemplate(string $template): array;

    /**
     * Get month name by month number
     *
     * @param int $month
     * @return string
     */
    public function getMonthName(int $month): string;

    /**
     * Get day names array
     *
     * @param string $dayType 'long', 'short', or 'abr'
     * @return array
     */
    public function getDayNames(string $dayType = 'abr'): array;

    /**
     * Adjust date for overflow/underflow months
     *
     * @param int $month
     * @param int $year
     * @return array [year, month]
     */
    public function adjustDate(int $month, int $year): array;

    /**
     * Get total days in a month
     *
     * @param int $month
     * @param int $year
     * @return int
     */
    public function getTotalDays(int $month, int $year): int;

    /**
     * Get last day of week for a given date
     *
     * @param int $month
     * @param int $year
     * @return int
     */
    public function getLastDay(int $month, int $year): int;

    /**
     * Get total weeks in a month
     *
     * @param int    $month
     * @param int    $year
     * @param string $days Day type
     * @return int
     */
    public function getTotalWeeks(int $month, int $year, string $days = 'long'): int;

    /**
     * Initialize calendar with configuration
     *
     * @param array $config
     * @return self
     */
    public function initialize(array $config = []): self;
}
