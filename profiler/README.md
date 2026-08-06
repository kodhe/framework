
## File Test `test_profiler.php`

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Kodhe\Library\Profiler\Profiler;

echo "<!DOCTYPE html>\n<html>\n<head>\n<title>Profiler Test</title>\n</head>\n<body>\n";
echo "<h1>Kodhe Profiler Test</h1>\n";

// Set some test data
$_GET['test'] = 'Hello World';
$_GET['page'] = 1;
$_POST['username'] = 'john_doe';
$_POST['action'] = 'save';

// Test 1: All sections
echo "<h2>Test 1: All Sections Enabled</h2>\n";
$profiler1 = new Profiler();
$output1 = $profiler1->run();
echo $output1;

// Test 2: Only benchmarks
echo "<h2>Test 2: Only Benchmarks</h2>\n";
$profiler2 = new Profiler();
$profiler2->disableAll();
$profiler2->set_sections(['benchmarks' => true]);
$output2 = $profiler2->run();
echo $output2;

// Test 3: Custom sections
echo "<h2>Test 3: GET, POST, Memory, URI</h2>\n";
$profiler3 = new Profiler();
$profiler3->disableAll();
$profiler3->set_sections([
    'get' => true,
    'post' => true,
    'memory_usage' => true,
    'uri_string' => true
]);
$output3 = $profiler3->run();
echo $output3;

// Test 4: No sections (empty state)
echo "<h2>Test 4: No Sections Enabled</h2>\n";
$profiler4 = new Profiler();
$profiler4->disableAll();
$output4 = $profiler4->run();
echo $output4;

echo "\n</body>\n</html>";