<?php

namespace Kodhe\Calendar\Generators;

/**
 * Class MonthGenerator
 *
 * Generates month calendar structure including weeks layout
 *
 * @package     Kodhe\Calendar
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class MonthGenerator
{
    /**
     * Cache for calculated structures
     *
     * @var array
     */
    private $cache = [];

    /**
     * Build calendar structure for a given month/year
     *
     * @param int|string $year   Year number
     * @param int|string $month  Month number (1-12)
     * @param array      $config Configuration options
     * @return array             Calendar structure
     */
    public function build($year, $month, array $config = []): array
    {
        $year  = (int) $year;
        $month = (int) $month;

        // Adjust for overflow/underflow
        [$year, $month] = $this->adjustDate($month, $year);

        $key = $year . '-' . $month;

        // Return cached result if available
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $structure = $this->calculate($year, $month, $config);
        $this->cache[$key] = $structure;

        return $structure;
    }

    /**
     * Calculate calendar structure
     *
     * @param int   $year
     * @param int   $month
     * @param array $config
     * @return array
     */
    private function calculate(int $year, int $month, array $config): array
    {
        $totalDays = $this->totalDays($month, $year);
        $startDay  = $this->getStartDay($month, $year, $config['start_day'] ?? 'sunday');

        // Build weeks array
        $weeks = $this->buildWeeks($totalDays, $startDay, $config);

        return [
            'year'       => $year,
            'month'      => $month,
            'total_days' => $totalDays,
            'start_day'  => $startDay,
            'weeks'      => $weeks,
        ];
    }

    /**
     * Build weeks array for the month
     *
     * @param int   $totalDays
     * @param int   $startDay
     * @param array $config
     * @return array
     */
    private function buildWeeks(int $totalDays, int $startDay, array $config): array
    {
        $weeks      = [];
        $currentDay = 1;
        $weekIndex  = 0;

        // Initialize first week with empty cells
        $weeks[$weekIndex] = array_fill(0, 7, null);

        // Fill in starting empty cells based on start day
        for ($i = 0; $i < $startDay; $i++) {
            $weeks[$weekIndex][$i] = null;
        }

        // Fill in actual days
        $dayOfWeek = $startDay;
        while ($currentDay <= $totalDays) {
            if ($dayOfWeek >= 7) {
                $dayOfWeek = 0;
                $weekIndex++;
                $weeks[$weekIndex] = array_fill(0, 7, null);
            }

            $weeks[$weekIndex][$dayOfWeek] = $currentDay;
            $currentDay++;
            $dayOfWeek++;
        }

        // Fill remaining cells in last week with null
        while ($dayOfWeek < 7) {
            $weeks[$weekIndex][$dayOfWeek] = null;
            $dayOfWeek++;
        }

        return $weeks;
    }

    /**
     * Adjust date for month overflow/underflow
     *
     * @param int $month
     * @param int $year
     * @return array [year, month]
     */
    public function adjustDate(int $month, int $year): array
    {
        if ($month < 1) {
            $month = 12 + $month;
            $year--;
        } elseif ($month > 12) {
            $month -= 12;
            $year++;
        }

        return [$year, $month];
    }

    /**
     * Get total days in a month
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
     * Get starting day of week (0-6)
     *
     * @param int    $month
     * @param int    $year
     * @param string $startDay 'sunday' or 'monday'
     * @return int
     */
    public function getStartDay(int $month, int $year, string $startDay = 'sunday'): int
    {
        $firstDay = (int) date('w', mktime(12, 0, 0, $month, 1, $year));

        if ($startDay === 'monday') {
            // Convert Sunday-based (0=Sunday) to Monday-based (0=Monday)
            $firstDay = ($firstDay + 6) % 7;
        }

        return $firstDay;
    }

    /**
     * Clear cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }
}
