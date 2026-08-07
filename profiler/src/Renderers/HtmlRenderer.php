<?php

declare(strict_types=1);

namespace Kodhe\Profiler\Renderers;

use Kodhe\Profiler\Contracts\RendererInterface;

/**
 * HTML Renderer
 * 
 * Renders profiler data as HTML
 */
class HtmlRenderer implements RendererInterface
{
    protected object $lang;
    protected string $charset = 'UTF-8';

    public function setLanguage(object $lang): void
    {
        $this->lang = $lang;
    }

    public function getType(): string
    {
        return 'html';
    }

    public function render(array $data): string
    {
        $output = '<div id="codeigniter_profiler" style="clear:both;background-color:#fff;padding:10px;">';
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
            $output .= '<p style="border:1px solid #5a0099;padding:10px;margin:20px 0;background-color:#eee;">'
                . $this->lang->line('profiler_no_profiles')
                . '</p>';
        }

        return $output . '</div>';
    }

    public function renderBenchmarks(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $output = "\n\n"
            . '<fieldset id="ci_profiler_benchmarks" style="border:1px solid #900;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee;">'
            . "\n"
            . '<legend style="color:#900;">&nbsp;&nbsp;' . $this->lang->line('profiler_benchmarks') . "&nbsp;&nbsp;</legend>\n\n\n"
            . '<table style="width:100%;">' . "\n";

        foreach ($data as $key => $val) {
            $key = ucwords(str_replace(['_', '-'], ' ', $key));
            $output .= '<tr><td style="padding:5px;width:50%;color:#000;font-weight:bold;background-color:#ddd;">'
                . $key . '&nbsp;&nbsp;</td><td style="padding:5px;width:50%;color:#900;font-weight:normal;background-color:#ddd;">'
                . $val . "</td></tr>\n";
        }

        return $output . "</table>\n</fieldset>";
    }

    public function renderMemoryUsage(array $data): string
    {
        $usage = $data['usage'] ?? 0;
        $display = ($usage != '') ? number_format($usage) . ' bytes' : $this->lang->line('profiler_no_memory');

        return "\n\n"
            . '<fieldset id="ci_profiler_memory_usage" style="border:1px solid #5a0099;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee;">'
            . "\n"
            . '<legend style="color:#5a0099;">&nbsp;&nbsp;' . $this->lang->line('profiler_memory_usage') . "&nbsp;&nbsp;</legend>\n"
            . '<div style="color:#5a0099;font-weight:normal;padding:4px 0 4px 0;">'
            . $display
            . '</div></fieldset>';
    }

    public function renderUriString(array $data): string
    {
        $uriString = $data['uri_string'] ?? '';
        $display = ($uriString === '') ? $this->lang->line('profiler_no_uri') : $uriString;

        return "\n\n"
            . '<fieldset id="ci_profiler_uri_string" style="border:1px solid #000;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee;">'
            . "\n"
            . '<legend style="color:#000;">&nbsp;&nbsp;' . $this->lang->line('profiler_uri_string') . "&nbsp;&nbsp;</legend>\n"
            . '<div style="color:#000;font-weight:normal;padding:4px 0 4px 0;">'
            . $display
            . '</div></fieldset>';
    }

    public function renderControllerInfo(array $data): string
    {
        $controller = $data['controller'] ?? '';
        $method = $data['method'] ?? '';

        return "\n\n"
            . '<fieldset id="ci_profiler_controller_info" style="border:1px solid #995300;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee;">'
            . "\n"
            . '<legend style="color:#995300;">&nbsp;&nbsp;' . $this->lang->line('profiler_controller_info') . "&nbsp;&nbsp;</legend>\n"
            . '<div style="color:#995300;font-weight:normal;padding:4px 0 4px 0;">'
            . $controller . '/' . $method
            . '</div></fieldset>';
    }

    public function renderHttpHeaders(array $data): string
    {
        $output = "\n\n"
            . '<fieldset id="ci_profiler_http_headers" style="border:1px solid #000;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee;">'
            . "\n"
            . '<legend style="color:#000;">&nbsp;&nbsp;' . $this->lang->line('profiler_headers')
            . '&nbsp;&nbsp;(<span style="cursor: pointer;" onclick="var s=document.getElementById(\'ci_profiler_httpheaders_table\').style;s.display=s.display==\'none\'?\'\':\'none\';this.innerHTML=this.innerHTML==\'' . $this->lang->line('profiler_section_show') . '\'?\'' . $this->lang->line('profiler_section_hide') . '\':\'' . $this->lang->line('profiler_section_show') . '\';">' . $this->lang->line('profiler_section_show') . "</span>)</legend>\n\n\n"
            . '<table style="width:100%;display:none;" id="ci_profiler_httpheaders_table">' . "\n";

        foreach ($data as $header => $value) {
            $value = htmlspecialchars($value ?? '', ENT_QUOTES, $this->charset);
            $output .= '<tr><td style="vertical-align:top;width:50%;padding:5px;color:#900;background-color:#ddd;">'
                . $header . '&nbsp;&nbsp;</td><td style="width:50%;padding:5px;color:#000;background-color:#ddd;">'
                . $value . "</td></tr>\n";
        }

        return $output . "</table>\n</fieldset>";
    }

    public function renderSessionData(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $output = '<fieldset id="ci_profiler_csession" style="border:1px solid #000;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee;">'
            . '<legend style="color:#000;">&nbsp;&nbsp;' . $this->lang->line('profiler_session_data')
            . '&nbsp;&nbsp;(<span style="cursor: pointer;" onclick="var s=document.getElementById(\'ci_profiler_session_data\').style;s.display=s.display==\'none\'?\'\':\'none\';this.innerHTML=this.innerHTML==\'' . $this->lang->line('profiler_section_show') . '\'?\'' . $this->lang->line('profiler_section_hide') . '\':\'' . $this->lang->line('profiler_section_show') . '\';">' . $this->lang->line('profiler_section_show') . '</span>)</legend>'
            . '<table style="width:100%;display:none;" id="ci_profiler_session_data">';

        foreach ($data as $key => $val) {
            $pre = '';
            $preClose = '';

            if (is_array($val) || is_object($val)) {
                $val = print_r($val, true);
                $pre = '<pre>';
                $preClose = '</pre>';
            }

            $output .= '<tr><td style="padding:5px;vertical-align:top;color:#900;background-color:#ddd;">'
                . $key . '&nbsp;&nbsp;</td><td style="padding:5px;color:#000;background-color:#ddd;">'
                . $pre . htmlspecialchars($val, ENT_QUOTES, $this->charset) . $preClose . "</td></tr>\n";
        }

        return $output . "</table>\n</fieldset>";
    }

    public function renderConfig(array $data): string
    {
        $output = "\n\n"
            . '<fieldset id="ci_profiler_config" style="border:1px solid #000;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee;">'
            . "\n"
            . '<legend style="color:#000;">&nbsp;&nbsp;' . $this->lang->line('profiler_config')
            . '&nbsp;&nbsp;(<span style="cursor: pointer;" onclick="var s=document.getElementById(\'ci_profiler_config_table\').style;s.display=s.display==\'none\'?\'\':\'none\';this.innerHTML=this.innerHTML==\'' . $this->lang->line('profiler_section_show') . '\'?\'' . $this->lang->line('profiler_section_hide') . '\':\'' . $this->lang->line('profiler_section_show') . '\';">' . $this->lang->line('profiler_section_show') . "</span>)</legend>\n\n\n"
            . '<table style="width:100%;display:none;" id="ci_profiler_config_table">' . "\n";

        foreach ($data as $config => $val) {
            $pre = '';
            $preClose = '';

            if (is_array($val) || is_object($val)) {
                $val = print_r($val, true);
                $pre = '<pre>';
                $preClose = '</pre>';
            }

            $output .= '<tr><td style="padding:5px;vertical-align:top;color:#900;background-color:#ddd;">'
                . $config . '&nbsp;&nbsp;</td><td style="padding:5px;color:#000;background-color:#ddd;">'
                . $pre . htmlspecialchars($val, ENT_QUOTES, $this->charset) . $preClose . "</td></tr>\n";
        }

        return $output . "</table>\n</fieldset>";
    }

    public function renderQueries(array $data): string
    {
        // Database rendering is complex - handled separately in Profiler
        return '';
    }

    public function renderGet(array $data): string
    {
        return $this->renderSuperGlobal('get', $data);
    }

    public function renderPost(array $data): string
    {
        return $this->renderSuperGlobal('post', $data);
    }

    protected function renderSuperGlobal(string $type, array $data): string
    {
        $colors = [
            'get' => '#cd6e00',
            'post' => '#009900'
        ];
        $color = $colors[$type] ?? '#000';
        $langKey = "profiler_{$type}_data";
        $noDataKey = "profiler_no_{$type}";
        $arrayRef = "\$_{$type}";

        $output = "\n\n"
            . "<fieldset id=\"ci_profiler_{$type}\" style=\"border:1px solid {$color};padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee;\">"
            . "\n"
            . "<legend style=\"color:{$color};\">&nbsp;&nbsp;" . $this->lang->line($langKey) . "&nbsp;&nbsp;</legend>\n";

        if (count($data) === 0) {
            $output .= '<div style="color:' . $color . ';font-weight:normal;padding:4px 0 4px 0;">'
                . $this->lang->line($noDataKey) . '</div>';
        } else {
            $output .= "\n\n<table style=\"width:100%;\">\n";

            foreach ($data as $key => $val) {
                $valDisplay = is_array($val) || is_object($val)
                    ? '<pre>' . htmlspecialchars(print_r($val, true), ENT_QUOTES, $this->charset) . '</pre>'
                    : htmlspecialchars($val, ENT_QUOTES, $this->charset);

                $output .= '<tr><td style="width:50%;padding:5px;color:#000;background-color:#ddd;">&#36;'
                    . $arrayRef . '[' . $key . ']&nbsp;&nbsp; </td><td style="width:50%;padding:5px;color:'
                    . $color . ';font-weight:normal;background-color:#ddd;">' . $valDisplay . "</td></tr>\n";
            }

            $output .= "</table>\n";
        }

        return $output . '</fieldset>';
    }
}
