<?php

declare(strict_types=0);

namespace Kodhe\Framework\Calendar\Contracts;

/**
 * Interface CalendarInterface
 *
 * @package Kodhe\Calendar\Contracts
 */
interface CalendarInterface
{
    /**
     * Generate the calendar
     *
     * @param int|string $year
     * @param int|string $month
     * @param array $data
     * @return string
     */
    public function generate($year = '', $month = '', $data = []): string;

    /**
     * Get month name
     *
     * @param int $month
     * @return string
     */
    public function getMonthName(int $month): string;

    /**
     * Get day names
     *
     * @param string $dayType
     * @return array
     */
    public function getDayNames(string $dayType = 'abr'): array;

    /**
     * Adjust date
     *
     * @param int $month
     * @param int $year
     * @return array
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
     * Get last day of month
     *
     * @param int $month
     * @param int $year
     * @return int
     */
    public function getLastDay(int $month, int $year): int;

    /**
     * Get total weeks
     *
     * @param int $month
     * @param int $year
     * @param array|null $days
     * @return int
     */
    public function getTotalWeeks(int $month, int $year, ?array $days = null): int;
}
