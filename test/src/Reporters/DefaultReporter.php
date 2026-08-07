<?php

declare(strict_types=1);

namespace Kodhe\Test\Reporters;

use Kodhe\Test\Contracts\ReporterInterface;
use Kodhe\Test\Formatters\HtmlFormatter;
use Kodhe\Test\Result\TestResultCollection;

/**
 * Default reporter that produces HTML output (legacy compatible)
 */
class DefaultReporter implements ReporterInterface
{
    /**
     * @var HtmlFormatter|null
     */
    private $formatter;

    /**
     * @var string|null Custom template
     */
    private $template;

    /**
     * @var string|null Template rows
     */
    private $templateRows;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->formatter = new HtmlFormatter();
    }

    /**
     * {@inheritdoc}
     */
    public function report(TestResultCollection $results): string
    {
        if ($results->count() === 0) {
            return '';
        }

        // Use custom template if set, otherwise use default formatter
        if ($this->template !== null) {
            return $this->renderWithTemplate($results);
        }

        return $this->formatter->format($results);
    }

    /**
     * Set custom template
     *
     * @param string $template Template string
     * @return void
     */
    public function setTemplate(string $template): void
    {
        $this->template = $template;
        $this->templateRows = null;
    }

    /**
     * Set template rows
     *
     * @param string $rows Template rows
     * @return void
     */
    public function setTemplateRows(string $rows): void
    {
        $this->templateRows = $rows;
    }

    /**
     * Render with custom template
     *
     * @param TestResultCollection $results Results to render
     * @return string
     */
    private function renderWithTemplate(TestResultCollection $results): string
    {
        // Parse template if needed
        $this->parseTemplate();

        $output = '';
        foreach ($results->toArray() as $res) {
            $table = '';

            foreach ($res as $key => $val) {
                if ($key === 'result') {
                    if ($val === 'passed') {
                        $val = '<span style="color: #0C0;">'. $val .'</span>';
                    } elseif ($val === 'failed') {
                        $val = '<span style="color: #C00;">'. $val .'</span>';
                    }
                }

                $table .= str_replace(
                    ['{item}', '{result}'],
                    [$key, $val],
                    $this->templateRows ?? ''
                );
            }

            $output .= str_replace('{rows}', $table, $this->template ?? '');
        }

        return $output;
    }

    /**
     * Parse template to extract rows
     *
     * @return void
     */
    private function parseTemplate(): void
    {
        if ($this->templateRows !== null) {
            return;
        }

        if ($this->template === null || !preg_match('/\{rows\}(.*?)\{\/rows\}/si', $this->template, $match)) {
            $this->setDefaultTemplate();
            return;
        }

        $this->templateRows = $match[1];
        $this->template = str_replace($match[0], '{rows}', $this->template);
    }

    /**
     * Set default template
     *
     * @return void
     */
    private function setDefaultTemplate(): void
    {
        $this->template = "\n".'<table style="width:100%; font-size:small; margin:10px 0; border-collapse:collapse; border:1px solid #CCC;">{rows}'."\n</table>";
        $this->templateRows = "\n\t<tr>\n\t\t".'<th style="text-align: left; border-bottom:1px solid #CCC;">{item}</th>'
            ."\n\t\t".'<td style="border-bottom:1px solid #CCC;">{result}</td>'."\n\t</tr>";
    }
}
