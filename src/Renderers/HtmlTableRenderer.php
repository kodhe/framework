<?php

namespace Kodhe\Calendar\Renderers;

use Kodhe\Calendar\Contracts\CalendarRendererInterface;
use Kodhe\Calendar\Localization\LexiconRepository;

/**
 * Class HtmlTableRenderer
 *
 * Renders calendar as HTML table (default CI3 format)
 *
 * @package     Kodhe\Calendar
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class HtmlTableRenderer implements CalendarRendererInterface
{
    /**
     * Lexicon repository for localized names
     *
     * @var LexiconRepository
     */
    private $lexiconRepository;

    /**
     * Constructor
     *
     * @param LexiconRepository|null $lexiconRepository
     */
    public function __construct(?LexiconRepository $lexiconRepository = null)
    {
        $this->lexiconRepository = $lexiconRepository ?? new LexiconRepository();
    }

    /**
     * Render calendar structure to HTML table
     *
     * @param array $structure Calendar structure from generator
     * @param array $data      Event/data for specific days
     * @param array $config    Configuration options
     * @return string          HTML table output
     */
    public function render(array $structure, array $data, array $config): string
    {
        $template = $config['template'] ?? $this->defaultTemplate();
        $locale   = $config['locale'] ?? 'en';
        $monthType = $config['month_type'] ?? 'long';
        $dayType   = $config['day_type'] ?? 'abr';
        $startDay  = $config['start_day'] ?? 'sunday';

        // Get localized names
        $monthName = $this->lexiconRepository->monthName($structure['month'], $locale, $monthType);
        $dayNames  = $this->lexiconRepository->dayNames($dayType, $locale);

        // Build heading
        $heading = $this->buildHeading($template, $monthName, $structure['year'], $config);

        // Build day names row
        $headers = $this->buildHeaders($template, $dayNames, $startDay);

        // Build calendar body
        $body = $this->buildBody($template, $structure['weeks'], $data, $config);

        // Combine all parts
        $output = str_replace(
            ['{heading}', '{week_days}', '{cal_body}'],
            [$heading, $headers, $body],
            $template['table_open'] ?? '<table border="0" cellpadding="4" cellspacing="0">{heading}{week_days}{cal_body}</table>'
        );

        return $output;
    }

    /**
     * Build heading section
     *
     * @param array  $template
     * @param string $monthName
     * @param int    $year
     * @param array  $config
     * @return string
     */
    private function buildHeading(array $template, string $monthName, int $year, array $config): string
    {
        $headingTitle = $template['heading_title_cell'] ?? '<th colspan="{colspan}" class="heading">{heading}</th>';
        $headingRow   = $template['heading_row_start'] ?? '<tr>' . $headingTitle . '</tr>';
        $headingClose = $template['heading_row_end'] ?? '';

        $title = str_replace('{heading}', $monthName . ' ' . $year, $headingTitle);
        $title = str_replace('{colspan}', '7', $title);

        return str_replace('{heading}', $title, $headingRow) . $headingClose;
    }

    /**
     * Build headers row with day names
     *
     * @param array  $template
     * @param array  $dayNames
     * @param string $startDay
     * @return string
     */
    private function buildHeaders(array $template, array $dayNames, string $startDay): string
    {
        $rowStart  = $template['week_day_cell'] ?? '<td>{content}</td>';
        $rowOpen   = $template['week_row_start'] ?? '<tr>';
        $rowClose  = $template['week_row_end'] ?? '</tr>';

        // Adjust day order based on start_day config
        if ($startDay === 'monday') {
            $dayNames = array_merge(array_slice($dayNames, 1), [array_shift($dayNames)]);
        }

        $headers = '';
        foreach ($dayNames as $day) {
            $headers .= str_replace('{content}', $day, $rowStart);
        }

        return $rowOpen . $headers . $rowClose;
    }

    /**
     * Build calendar body with weeks and days
     *
     * @param array $template
     * @param array $weeks
     * @param array $data
     * @param array $config
     * @return string
     */
    private function buildBody(array $template, array $weeks, array $data, array $config): string
    {
        $rowStart    = $template['cal_row_start'] ?? '<tr>';
        $rowEnd      = $template['cal_row_end'] ?? '</tr>';
        $cellContent = $template['cal_cell_content'] ?? '<a href="{content}">{day}</a>';
        $cellNoContent = $template['cal_cell_no_content'] ?? '{day}';
        $cellStart   = $template['cal_cell_start'] ?? '<td>';
        $cellEnd     = $template['cal_cell_end'] ?? '</td>';
        $cellToday   = $template['cal_cell_content_today'] ?? '<a href="{content}"><strong>{day}</strong></a>';
        $cellNoContentToday = $template['cal_cell_no_content_today'] ?? '<strong>{day}</strong>';

        $today = (int) date('j');
        $currentMonth = (int) date('n');
        $currentYear = (int) date('Y');

        $body = '';
        foreach ($weeks as $week) {
            $row = $rowStart;

            foreach ($week as $day) {
                if ($day === null) {
                    // Empty cell
                    $row .= $cellStart . '&nbsp;' . $cellEnd;
                } else {
                    // Check if today
                    $isToday = ($day === $today && 
                               $currentMonth === $config['_month'] ?? 0 && 
                               $currentYear === $config['_year'] ?? 0);

                    // Check if there's data for this day
                    if (isset($data[$day])) {
                        $dayData = $data[$day];
                        
                        if (is_array($dayData)) {
                            $content = $dayData['url'] ?? '';
                            $display = $dayData['title'] ?? $day;
                        } else {
                            $content = $dayData;
                            $display = $day;
                        }

                        $cell = $isToday ? $cellToday : $cellContent;
                        $cell = str_replace(['{content}', '{day}'], [$content, $display], $cell);
                        $row .= $cellStart . $cell . $cellEnd;
                    } else {
                        $cell = $isToday ? $cellNoContentToday : $cellNoContent;
                        $cell = str_replace('{day}', $day, $cell);
                        $row .= $cellStart . $cell . $cellEnd;
                    }
                }
            }

            $body .= $row . $rowEnd;
        }

        return $body;
    }

    /**
     * Get default template array
     *
     * @return array
     */
    public function defaultTemplate(): array
    {
        return [
            'table_open'                 => '<table border="0" cellpadding="4" cellspacing="0">',
            'heading_row_start'          => '<tr>',
            'heading_title_cell'         => '<th colspan="{colspan}" class="heading">{heading}</th>',
            'heading_row_end'            => '</tr>',
            'week_row_start'             => '<tr>',
            'week_day_cell'              => '<td>{content}</td>',
            'week_row_end'               => '</tr>',
            'cal_row_start'              => '<tr>',
            'cal_cell_start'             => '<td>',
            'cal_cell_content'           => '<a href="{content}">{day}</a>',
            'cal_cell_no_content'        => '{day}',
            'cal_cell_content_today'     => '<a href="{content}"><strong>{day}</strong></a>',
            'cal_cell_no_content_today'  => '<strong>{day}</strong>',
            'cal_cell_end'               => '</td>',
            'cal_row_end'                => '</tr>',
            'table_close'                => '</table>',
        ];
    }

    /**
     * Set lexicon repository
     *
     * @param LexiconRepository $repository
     * @return self
     */
    public function setLexiconRepository(LexiconRepository $repository): self
    {
        $this->lexiconRepository = $repository;
        return $this;
    }
}
