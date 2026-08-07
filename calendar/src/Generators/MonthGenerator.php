<?php

declare(strict_types=1);

namespace Kodhe\Calendar\Generators;

/**
 * Class MonthGenerator
 *
 * Generates calendar month structure
 *
 * @package Kodhe\Calendar\Generators
 */
class MonthGenerator
{
    /**
     * Cache for calculated structures
     *
     * @var array
     */
    private static $cache = [];

    /**
     * Build month structure
     *
     * @param int|string $year
     * @param int|string $month
     * @param array $config
     * @return array
     */
    public function build($year, $month, array $config = []): array
    {
        $key = $year . '-' . $month;

        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        [$year, $month] = $this->adjustDate((int)$month, (int)$year);
        
        $totalDays = $this->totalDays((int)$month, (int)$year);
        $startDay = $this->getStartDay((int)$month, (int)$year, $config['start_day'] ?? 'sunday');
        $weeks = $this->buildWeeks($totalDays, $startDay, $config);

        $structure = [
            'year' => $year,
            'month' => $month,
            'total_days' => $totalDays,
            'start_day' => $startDay,
            'weeks' => $weeks,
        ];

        self::$cache[$key] = $structure;

        return $structure;
    }

    /**
     * Adjust date to valid month/year
     *
     * @param int $month
     * @param int $year
     * @return array
     */
    public function adjustDate(int $month, int $year): array
    {
        while ($month > 12) {
            $month -= 12;
            $year++;
        }

        while ($month <= 0) {
            $month += 12;
            $year--;
        }

        return [$year, $month];
    }

    /**
     * Get total days in month
     *
     * @param int $month
     * @param int $year
     * @return int
     */
    public function totalDays(int $month, int $year): int
    {
        return (int) date('t', mktime(12, 0, 0, $month, 1, $year));
    }

    /**
     * Get starting day of week
     *
     * @param int $month
     * @param int $year
     * @param string $startDay
     * @return int
     */
    public function getStartDay(int $month, int $year, string $startDay = 'sunday'): int
    {
        $startDays = [
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
        ];

        $start = $startDays[$startDay] ?? 0;
        $localDate = mktime(12, 0, 0, $month, 1, $year);
        $date = getdate($localDate);
        $day = $start + 1 - $date['wday'];

        while ($day > 1) {
            $day -= 7;
        }

        return $day;
    }

    /**
     * Build weeks array
     *
     * @param int $totalDays
     * @param int $startDay
     * @param array $config
     * @return array
     */
    public function buildWeeks(int $totalDays, int $startDay, array $config = []): array
    {
        $weeks = [];
        $week = [];
        $day = $startDay;

        // Fill empty cells before first day
        while ($day > 1) {
            $prevMonth = $this->adjustDate((int)date('m') - 1, (int)date('Y'));
            $prevMonthDays = $this->totalDays($prevMonth[1], $prevMonth[0]);
            $week[] = ['day' => $prevMonthDays + $day, 'is_current_month' => false, 'type' => 'prev'];
            $day++;
        }

        // Fill actual days
        for ($d = 1; $d <= $totalDays; $d++) {
            $week[] = ['day' => $d, 'is_current_month' => true, 'type' => 'current'];

            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }
        }

        // Fill remaining cells
        if (!empty($week)) {
            while (count($week) < 7) {
                $nextMonthDay = count($week) - (7 - $totalDays % 7) + 1;
                if ($nextMonthDay <= 0) {
                    $nextMonthDay = count($week) + 1;
                }
                $week[] = ['day' => $nextMonthDay, 'is_current_month' => false, 'type' => 'next'];
            }
            $weeks[] = $week;
        }

        return $weeks;
    }

    /**
     * Clear cache
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
