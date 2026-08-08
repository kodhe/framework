<?php

declare(strict_types=1);

namespace Kodhe\Framework\Calendar\Renderers;

use Kodhe\Framework\Calendar\Contracts\CalendarRendererInterface;

/**
 * Class HtmlTableRenderer
 *
 * Renders calendar as HTML table (default CI3 style)
 *
 * @package Kodhe\Calendar\Renderers
 */
class HtmlTableRenderer implements CalendarRendererInterface
{
    /**
     * Render calendar structure to HTML
     *
     * @param array $structure
     * @param array $data
     * @param array $config
     * @return string
     */
    public function render(array $structure, array $data, array $config): string
    {
        $template = $this->parseTemplate($config);
        
        // Determine current date
        $localTime = $config['local_time'] ?? time();
        $curYear = (int) date('Y', $localTime);
        $curMonth = (int) date('m', $localTime);
        $curDay = (int) date('j', $localTime);
        $isCurrentMonth = ($curYear == $structure['year'] && $curMonth == $structure['month']);

        // Build output
        $out = $template['table_open']."\n\n".$template['heading_row_start']."\n";

        // Previous month link
        if ($config['show_next_prev'] ?? false) {
            $adjustedDate = $this->adjustDate((int)$structure['month'] - 1, (int)$structure['year']);
            $prevUrl = ($config['next_prev_url'] ?? '').$adjustedDate['year'].'/'.$adjustedDate['month'];
            $out .= str_replace('{previous_url}', $prevUrl, $template['heading_previous_cell'])."\n";
        }

        // Month/year heading
        $colspan = ($config['show_next_prev'] ?? false) ? 5 : 7;
        $monthName = $this->getMonthName((int)$structure['month'], $config);
        $heading = str_replace(
            ['{colspan}', '{heading}'],
            [$colspan, $monthName.'&nbsp;'.$structure['year']],
            $template['heading_title_cell']
        );
        $out .= $heading."\n";

        // Next month link
        if ($config['show_next_prev'] ?? false) {
            $adjustedDate = $this->adjustDate((int)$structure['month'] + 1, (int)$structure['year']);
            $nextUrl = ($config['next_prev_url'] ?? '').$adjustedDate['year'].'/'.$adjustedDate['month'];
            $out .= str_replace('{next_url}', $nextUrl, $template['heading_next_cell']);
        }

        $out .= "\n".$template['heading_row_end']."\n\n".$template['week_row_start']."\n";

        // Day names header
        $dayNames = $this->getDayNames($config['day_type'] ?? 'abr', $config);
        $startDay = $this->getStartDayIndex($config['start_day'] ?? 'sunday');

        for ($i = 0; $i < 7; $i++) {
            $out .= str_replace('{week_day}', $dayNames[($startDay + $i) % 7], $template['week_day_cell']);
        }

        $out .= "\n".$template['week_row_end']."\n";

        // Build calendar body
        foreach ($structure['weeks'] as $weekIndex => $week) {
            $out .= "\n".$template['cal_row_start']."\n";

            foreach ($week as $cellIndex => $cell) {
                $dayNum = $cell['day'];
                $isCurrentMonthCell = $cell['is_current_month'];
                $isToday = ($isCurrentMonth && $dayNum == $curDay && $cell['type'] === 'current');

                if ($isCurrentMonthCell && $cell['type'] === 'current') {
                    $out .= $isToday ? $template['cal_cell_start_today'] : $template['cal_cell_start'];

                    if (isset($data[$dayNum])) {
                        $temp = $isToday ? $template['cal_cell_content_today'] : $template['cal_cell_content'];
                        $out .= str_replace(['{content}', '{day}'], [$data[$dayNum], $dayNum], $temp);
                    } else {
                        $temp = $isToday ? $template['cal_cell_no_content_today'] : $template['cal_cell_no_content'];
                        $out .= str_replace('{day}', (string)$dayNum, $temp);
                    }

                    $out .= $isToday ? $template['cal_cell_end_today'] : $template['cal_cell_end'];
                } elseif (($config['show_other_days'] ?? false) || !$isCurrentMonthCell) {
                    if ($config['show_other_days'] ?? false) {
                        $out .= $template['cal_cell_start_other'];
                        
                        if ($cell['type'] === 'prev') {
                            $out .= str_replace('{day}', (string)$dayNum, $template['cal_cell_other']);
                        } else {
                            $out .= str_replace('{day}', (string)$dayNum, $template['cal_cell_other']);
                        }
                        
                        $out .= $template['cal_cell_end_other'];
                    } else {
                        $out .= $template['cal_cell_start'].$template['cal_cell_blank'].$template['cal_cell_end'];
                    }
                } else {
                    $out .= $template['cal_cell_start'].$template['cal_cell_blank'].$template['cal_cell_end'];
                }
            }

            $out .= "\n".$template['cal_row_end']."\n";
        }

        return $out."\n".$template['table_close'];
    }

    /**
     * Get default template
     *
     * @return array
     */
    public function defaultTemplate(): array
    {
        return [
            'table_open' => '<table border="0" cellpadding="4" cellspacing="0">',
            'heading_row_start' => '<tr>',
            'heading_previous_cell' => '<th><a href="{previous_url}">&lt;&lt;</a></th>',
            'heading_title_cell' => '<th colspan="{colspan}">{heading}</th>',
            'heading_next_cell' => '<th><a href="{next_url}">&gt;&gt;</a></th>',
            'heading_row_end' => '</tr>',
            'week_row_start' => '<tr>',
            'week_day_cell' => '<td>{week_day}</td>',
            'week_row_end' => '</tr>',
            'cal_row_start' => '<tr>',
            'cal_cell_start' => '<td>',
            'cal_cell_start_today' => '<td>',
            'cal_cell_start_other' => '<td style="color: #666;">',
            'cal_cell_content' => '<a href="{content}">{day}</a>',
            'cal_cell_content_today' => '<a href="{content}"><strong>{day}</strong></a>',
            'cal_cell_no_content' => '{day}',
            'cal_cell_no_content_today' => '<strong>{day}</strong>',
            'cal_cell_blank' => '&nbsp;',
            'cal_cell_other' => '{day}',
            'cal_cell_end' => '</td>',
            'cal_cell_end_today' => '</td>',
            'cal_cell_end_other' => '</td>',
            'cal_row_end' => '</tr>',
            'table_close' => '</table>',
        ];
    }

    /**
     * Parse template from config
     *
     * @param array $config
     * @return array
     */
    private function parseTemplate(array $config): array
    {
        $replacements = $this->defaultTemplate();
        $template = $config['template'] ?? null;

        if (empty($template)) {
            return $replacements;
        }

        if (is_string($template)) {
            $today = ['cal_cell_start_today', 'cal_cell_content_today', 'cal_cell_no_content_today', 'cal_cell_end_today'];
            
            foreach (array_keys($replacements) as $val) {
                if (preg_match('/\\{'.$val.'\\}(.*?)\\{\\/'.$val.'\\}/si', $template, $match)) {
                    $replacements[$val] = $match[1];
                } elseif (in_array($val, $today, true)) {
                    $replacements[$val] = $replacements[substr($val, 0, -6)];
                }
            }
        } elseif (is_array($template)) {
            $replacements = array_merge($replacements, $template);
        }

        return $replacements;
    }

    /**
     * Adjust date helper
     *
     * @param int $month
     * @param int $year
     * @return array
     */
    private function adjustDate(int $month, int $year): array
    {
        while ($month > 12) {
            $month -= 12;
            $year++;
        }

        while ($month <= 0) {
            $month += 12;
            $year--;
        }

        return ['year' => $year, 'month' => sprintf('%02d', $month)];
    }

    /**
     * Get month name
     *
     * @param int $month
     * @param array $config
     * @return string
     */
    private function getMonthName(int $month, array $config): string
    {
        $monthType = $config['month_type'] ?? 'long';
        $locale = $config['locale'] ?? 'en';
        
        $months = [
            'en' => [
                'short' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                'long' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            ],
            'id' => [
                'short' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                'long' => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            ],
        ];

        $langData = $months[$locale] ?? $months['en'];
        return $langData[$monthType][$month - 1] ?? '';
    }

    /**
     * Get day names
     *
     * @param string $dayType
     * @param array $config
     * @return array
     */
    private function getDayNames(string $dayType, array $config): array
    {
        $locale = $config['locale'] ?? 'en';
        
        $days = [
            'en' => [
                'long' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                'short' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                'abr' => ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
            ],
            'id' => [
                'long' => ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
                'short' => ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                'abr' => ['Mi', 'Se', 'Se', 'Ra', 'Ka', 'Ju', 'Sa'],
            ],
        ];

        $langData = $days[$locale] ?? $days['en'];
        return $langData[$dayType] ?? $langData['abr'];
    }

    /**
     * Get start day index
     *
     * @param string $startDay
     * @return int
     */
    private function getStartDayIndex(string $startDay): int
    {
        $map = [
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
        ];

        return $map[$startDay] ?? 0;
    }
}
