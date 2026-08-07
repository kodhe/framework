<?php

namespace Kodhe\Calendar\ValueObjects;

/**
 * Class CalendarDate
 *
 * Value object representing a calendar date
 * Immutable value object for year, month, day representation
 *
 * @package     Kodhe\Calendar
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class CalendarDate
{
    /**
     * Year number
     *
     * @var int
     */
    private $year;

    /**
     * Month number (1-12)
     *
     * @var int
     */
    private $month;

    /**
     * Day number (1-31)
     *
     * @var int|null
     */
    private $day;

    /**
     * Constructor
     *
     * @param int      $year  Year number
     * @param int      $month Month number (1-12)
     * @param int|null $day   Day number (1-31), null for month-only
     */
    public function __construct(int $year, int $month, ?int $day = null)
    {
        $this->validateMonth($month);
        
        if ($day !== null) {
            $this->validateDay($year, $month, $day);
        }

        $this->year  = $year;
        $this->month = $month;
        $this->day   = $day;
    }

    /**
     * Validate month number
     *
     * @param int $month
     * @throws \InvalidArgumentException
     * @return void
     */
    private function validateMonth(int $month): void
    {
        if ($month < 1 || $month > 12) {
            throw new \InvalidArgumentException(
                sprintf('Month must be between 1 and 12, got %d', $month)
            );
        }
    }

    /**
     * Validate day number
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @throws \InvalidArgumentException
     * @return void
     */
    private function validateDay(int $year, int $month, int $day): void
    {
        $maxDay = (int) date('t', mktime(12, 0, 0, $month, 1, $year));
        
        if ($day < 1 || $day > $maxDay) {
            throw new \InvalidArgumentException(
                sprintf('Day must be between 1 and %d for %d-%02d, got %d', $maxDay, $year, $month, $day)
            );
        }
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
     * Check if date has day component
     *
     * @return bool
     */
    public function hasDay(): bool
    {
        return $this->day !== null;
    }

    /**
     * Get total days in this month
     *
     * @return int
     */
    public function getTotalDays(): int
    {
        return (int) date('t', mktime(12, 0, 0, $this->month, 1, $this->year));
    }

    /**
     * Get formatted date string
     *
     * @param string $format PHP date format
     * @return string
     */
    public function format(string $format): string
    {
        if ($this->day === null) {
            // Month only - use first day of month
            return date($format, mktime(12, 0, 0, $this->month, 1, $this->year));
        }

        return date($format, mktime(12, 0, 0, $this->month, $this->day, $this->year));
    }

    /**
     * Get ISO 8601 date string
     *
     * @return string
     */
    public function toIsoString(): string
    {
        if ($this->day === null) {
            return sprintf('%04d-%02d', $this->year, $this->month);
        }

        return sprintf('%04d-%02d-%02d', $this->year, $this->month, $this->day);
    }

    /**
     * Create from DateTime object
     *
     * @param \DateTimeInterface $dateTime
     * @return self
     */
    public static function fromDateTime(\DateTimeInterface $dateTime): self
    {
        return new self(
            (int) $dateTime->format('Y'),
            (int) $dateTime->format('n'),
            (int) $dateTime->format('j')
        );
    }

    /**
     * Create from ISO 8601 string
     *
     * @param string $isoString
     * @return self
     */
    public static function fromIsoString(string $isoString): self
    {
        $parts = explode('-', $isoString);
        $year  = (int) ($parts[0] ?? date('Y'));
        $month = (int) ($parts[1] ?? date('n'));
        $day   = isset($parts[2]) ? (int) $parts[2] : null;

        return new self($year, $month, $day);
    }

    /**
     * Check equality with another date
     *
     * @param CalendarDate $other
     * @return bool
     */
    public function equals(CalendarDate $other): bool
    {
        return $this->year === $other->year
            && $this->month === $other->month
            && $this->day === $other->day;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'year'  => $this->year,
            'month' => $this->month,
            'day'   => $this->day,
        ];
    }
}
