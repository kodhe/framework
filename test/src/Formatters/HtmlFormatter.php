<?php

declare(strict_types=0);

namespace Kodhe\Framework\Test\Formatters;

use Kodhe\Framework\Test\Contracts\FormatterInterface;
use Kodhe\Framework\Test\Result\TestResultCollection;

/**
 * Formats test results as HTML
 */
class HtmlFormatter implements FormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function format(TestResultCollection $results): string
    {
        if ($results->count() === 0) {
            return '';
        }

        $html = "<table style=\"width:100%; font-size:small; margin:10px 0; border-collapse:collapse; border:1px solid #CCC;\">";

        foreach ($results as $result) {
            $html .= "\n\t<tr>";
            $html .= "\n\t\t<th style=\"text-align: left; border-bottom:1px solid #CCC;\">Test Name</th>";
            $html .= "\n\t\t<td style=\"border-bottom:1px solid #CCC;\">". $this->escape($result->getTestName()) ."</td>";
            $html .= "\n\t</tr>";
            $html .= "\n\t<tr>";
            $html .= "\n\t\t<th style=\"text-align: left; border-bottom:1px solid #CCC;\">Test Datatype</th>";
            $html .= "\n\t\t<td style=\"border-bottom:1px solid #CCC;\">". $this->escape($result->getTestDatatype()) ."</td>";
            $html .= "\n\t</tr>";
            $html .= "\n\t<tr>";
            $html .= "\n\t\t<th style=\"text-align: left; border-bottom:1px solid #CCC;\">Expected Datatype</th>";
            $html .= "\n\t\t<td style=\"border-bottom:1px solid #CCC;\">". $this->escape($result->getResDatatype()) ."</td>";
            $html .= "\n\t</tr>";
            $html .= "\n\t<tr>";
            $html .= "\n\t\t<th style=\"text-align: left; border-bottom:1px solid #CCC;\">Result</th>";
            $html .= "\n\t\t<td style=\"border-bottom:1px solid #CCC;\">". $this->formatResult($result->getResult()) ."</td>";
            $html .= "\n\t</tr>";
            $html .= "\n\t<tr>";
            $html .= "\n\t\t<th style=\"text-align: left; border-bottom:1px solid #CCC;\">File</th>";
            $html .= "\n\t\t<td style=\"border-bottom:1px solid #CCC;\">". $this->escape($result->getFile()) ."</td>";
            $html .= "\n\t</tr>";
            $html .= "\n\t<tr>";
            $html .= "\n\t\t<th style=\"text-align: left; border-bottom:1px solid #CCC;\">Line</th>";
            $html .= "\n\t\t<td style=\"border-bottom:1px solid #CCC;\">". $result->getLine() ."</td>";
            $html .= "\n\t</tr>";
            $html .= "\n\t<tr>";
            $html .= "\n\t\t<th style=\"text-align: left; border-bottom:1px solid #CCC;\">Notes</th>";
            $html .= "\n\t\t<td style=\"border-bottom:1px solid #CCC;\">". $this->escape($result->getNotes()) ."</td>";
            $html .= "\n\t</tr>";
            $html .= "\n</table>";
        }

        return $html;
    }

    /**
     * Format result with color
     *
     * @param string $result Result status
     * @return string
     */
    private function formatResult(string $result): string
    {
        if ($result === 'passed') {
            return '<span style="color: #0C0;">'. $result .'</span>';
        }

        if ($result === 'failed') {
            return '<span style="color: #C00;">'. $result .'</span>';
        }

        return $result;
    }

    /**
     * Escape HTML special characters
     *
     * @param string $string String to escape
     * @return string
     */
    private function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}
