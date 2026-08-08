<?php

declare(strict_types=1);

namespace Kodhe\Framework\Profiler\Renderers;

use Kodhe\Framework\Profiler\Contracts\RendererInterface;

/**
 * Text Renderer
 * 
 * Renders profiler data as plain text
 */
class TextRenderer implements RendererInterface
{
    protected object $lang;

    public function setLanguage(object $lang): void
    {
        $this->lang = $lang;
    }

    public function getType(): string
    {
        return 'text';
    }

    public function render(array $data): string
    {
        $output = "=== CodeIgniter Profiler ===\n\n";
        $fieldsDisplayed = 0;

        foreach ($data as $section => $sectionData) {
            if (!$sectionData['enabled'] ?? true) {
                continue;
            }

            $renderMethod = 'render' . ucfirst($section);
            if (method_exists($this, $renderMethod)) {
                $output .= $this->$renderMethod($sectionData['data'] ?? []);
                $fieldsDisplayed++;
            }
        }

        if ($fieldsDisplayed === 0) {
            $output .= $this->lang->line('profiler_no_profiles') . "\n";
        }

        return $output;
    }

    public function renderBenchmarks(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $output = "--- Benchmarks ---\n";
        foreach ($data as $key => $val) {
            $key = ucwords(str_replace(['_', '-'], ' ', $key));
            $output .= sprintf("%-30s %s\n", $key, $val);
        }
        return $output . "\n";
    }

    public function renderMemoryUsage(array $data): string
    {
        $usage = $data['usage'] ?? 0;
        $display = ($usage != '') ? number_format($usage) . ' bytes' : 'N/A';

        return "--- Memory Usage ---\n{$display}\n\n";
    }

    public function renderUriString(array $data): string
    {
        $uriString = $data['uri_string'] ?? '';
        $display = ($uriString === '') ? '(no URI)' : $uriString;

        return "--- URI String ---\n{$display}\n\n";
    }

    public function renderControllerInfo(array $data): string
    {
        $controller = $data['controller'] ?? '';
        $method = $data['method'] ?? '';

        return "--- Controller Info ---\n{$controller}/{$method}\n\n";
    }

    public function renderHttpHeaders(array $data): string
    {
        $output = "--- HTTP Headers ---\n";
        foreach ($data as $header => $value) {
            $output .= sprintf("%-25s: %s\n", $header, $value ?? '');
        }
        return $output . "\n";
    }

    public function renderSessionData(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $output = "--- Session Data ---\n";
        foreach ($data as $key => $val) {
            $valDisplay = is_array($val) || is_object($val)
                ? print_r($val, true)
                : $val;
            $output .= sprintf("%-20s: %s\n", $key, $valDisplay);
        }
        return $output . "\n";
    }

    public function renderConfig(array $data): string
    {
        $output = "--- Config ---\n";
        foreach ($data as $config => $val) {
            $valDisplay = is_array($val) || is_object($val)
                ? print_r($val, true)
                : $val;
            $output .= sprintf("%-25s: %s\n", $config, $valDisplay);
        }
        return $output . "\n";
    }

    public function renderQueries(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $output = "--- Database Queries ---\n";
        foreach ($data as $name => $dbData) {
            $output .= "\nDatabase: {$dbData['database']} ({$name})\n";
            $output .= "Queries: {$dbData['query_count']}, Total Time: " . number_format($dbData['total_time'], 4) . " seconds\n";

            foreach ($dbData['queries'] as $key => $query) {
                $time = number_format($dbData['query_times'][$key], 4);
                $output .= "[{$time}] {$query}\n";
            }
        }
        return $output . "\n";
    }

    public function renderGet(array $data): string
    {
        return $this->renderSuperGlobal('GET', $data);
    }

    public function renderPost(array $data): string
    {
        return $this->renderSuperGlobal('POST', $data);
    }

    protected function renderSuperGlobal(string $type, array $data): string
    {
        if (count($data) === 0) {
            return "--- \${$type} ---\n(no data)\n\n";
        }

        $output = "--- \${$type} ---\n";
        foreach ($data as $key => $val) {
            $valDisplay = is_array($val) || is_object($val)
                ? print_r($val, true)
                : $val;
            $output .= sprintf("[%s] => %s\n", $key, $valDisplay);
        }
        return $output . "\n";
    }
}
