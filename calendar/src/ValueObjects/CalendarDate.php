<?php

declare(strict_types=1);

namespace Kodhe\Framework\Calendar\ValueObjects;

/**
 * Class CalendarDate
 *
 * Immutable value object representing a calendar date
 *
 * @package Kodhe\Calendar\ValueObjects
 */
class CalendarDate
{
    /**
     * Year
     *
     * @var int
     */
    private $year;

    /**
     * Month
     *
     * @var int
     */
    private $month;

    /**
     * Day
     *
     * @var int|null
     */
    private $day;

    /**
     * Constructor
     *
     * @param int $year
     * @param int $month
     * @param int|null $day
     */
    public function __construct(int $year, int $month, ?int $day = null)
    {
        $this->year = $year;
        $this->month = max(1, min(12, $month));
        $this->day = $day !== null ? max(1, min(31, $day)) : null;
    }

    /**
     * Get year
     *
     * @return int
     */
    public function getYear(): int
    {
        return $this->year;
    }

    /**
     * Get month
     *
     * @return int
     */
    public function getMonth(): int
    {
        return $this->month;
    }

    /**
     * Get day
     *
     * @return int|null
     */
    public function getDay(): ?int
    {
        return $this->day;
    }

    /**
     * Check if date is complete (has day)
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        return $this->day !== null;
    }

    /**
     * Format date as string
     *
     * @param string $format
     * @return string
     */
    public function format(string $format = 'Y-m-d'): string
    {
        if ($this->day === null) {
            return sprintf('%04d-%02d', $this->year, $this->month);
        }

        return date($format, mktime(0, 0, 0, $this->month, $this->day, $this->year));
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'year' => $this->year,
            'month' => $this->month,
            'day' => $this->day,
        ];
    }

    /**
     * String representation
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->format();
    }
}
