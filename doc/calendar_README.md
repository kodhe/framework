
## File Test `test_calendar.php`

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Kodhe\Library\Calendar\Calendar;

echo "<!DOCTYPE html>\n<html>\n<head>\n";
echo "<title>Calendar Test</title>\n";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h2 { color: #333; margin-top: 30px; }
    table { border-collapse: collapse; margin-bottom: 20px; }
    th { background: #f5f5f5; padding: 8px; text-align: center; }
    td { padding: 8px; text-align: center; border: 1px solid #ddd; }
    td:hover { background: #f0f0f0; }
</style>\n";
echo "</head>\n<body>\n";

// Test 1: Basic calendar
echo "<h2>Test 1: Basic Calendar (Current Month)</h2>\n";
$cal1 = new Calendar();
echo $cal1->generate();

// Test 2: Specific month
echo "<h2>Test 2: January 2024</h2>\n";
$cal2 = new Calendar();
echo $cal2->generate(2024, 1);

// Test 3: With navigation
echo "<h2>Test 3: Calendar with Prev/Next Links</h2>\n";
$cal3 = new Calendar([
    'show_next_prev' => true,
    'next_prev_url' => '/calendar/',
]);
echo $cal3->generate(2024, 1);

// Test 4: Start on Monday, show other days
echo "<h2>Test 4: Start Monday + Show Other Days</h2>\n";
$cal4 = new Calendar([
    'start_day' => 'monday',
    'show_other_days' => true,
]);
echo $cal4->generate(2024, 1);

// Test 5: With data (event links)
echo "<h2>Test 5: Calendar with Event Links</h2>\n";
$data = [
    1 => '/events/new-year',
    15 => '/events/meeting',
    25 => '/events/workshop',
];
$cal5 = new Calendar();
echo $cal5->generate(2024, 1, $data);

// Test 6: Short month names, short day names
echo "<h2>Test 6: Short Names + Sunday Start</h2>\n";
$cal6 = new Calendar([
    'month_type' => 'short',
    'day_type' => 'short',
    'start_day' => 'sunday',
]);
echo $cal6->generate(2024, 3);

// Test 7: Edge case - December to January navigation
echo "<h2>Test 7: December 2024 (with navigation)</h2>\n";
$cal7 = new Calendar([
    'show_next_prev' => true,
    'next_prev_url' => '/calendar/',
]);
echo $cal7->generate(2024, 12);

// Test 8: Template customization
echo "<h2>Test 8: Custom Template</h2>\n";
$custom_template = '
{table_open}<table class="custom-cal">{/table_open}
{heading_row_start}<tr>{/heading_row_start}
{heading_title_cell}<th colspan="{colspan}" style="background:#333;color:white;">{heading}</th>{/heading_title_cell}
{heading_row_end}</tr>{/heading_row_end}
{week_row_start}<tr style="background:#eee;">{/week_row_start}
{week_day_cell}<td><strong>{week_day}</strong></td>{/week_day_cell}
{week_row_end}</tr>{/week_row_end}
{cal_row_start}<tr>{/cal_row_start}
{cal_cell_start}<td>{/cal_cell_start}
{cal_cell_content}<a href="{content}">{day}</a>{/cal_cell_content}
{cal_cell_no_content}{day}{/cal_cell_no_content}
{cal_cell_blank}&nbsp;{/cal_cell_blank}
{cal_cell_end}</td>{/cal_cell_end}
{cal_row_end}</tr>{/cal_row_end}
{table_close}</table>{/table_close}
';
$cal8 = new Calendar(['template' => $custom_template]);
echo $cal8->generate(2024, 1, $data);

echo "\n</body>\n</html>";