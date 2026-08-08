<?php

declare(strict_types=1);

namespace Kodhe\Framework\Pagination\Renderers;

use Kodhe\Framework\Pagination\ValueObjects\LinkData;

/**
 * Bootstrap 4/5 Pagination Renderer
 */
class BootstrapRenderer extends DefaultRenderer
{
    public function __construct()
    {
        $this->config = [
            'full_tag_open' => '<nav><ul class="pagination">',
            'full_tag_close' => '</ul></nav>',
            'first_tag_open' => '<li class="page-item"><span class="page-link">',
            'first_tag_close' => '</span></li>',
            'last_tag_open' => '<li class="page-item"><span class="page-link">',
            'last_tag_close' => '</span></li>',
            'cur_tag_open' => '<li class="page-item active"><span class="page-link">',
            'cur_tag_close' => '</span></li>',
            'next_tag_open' => '<li class="page-item"><a class="page-link"',
            'next_tag_close' => '</a></li>',
            'prev_tag_open' => '<li class="page-item"><a class="page-link"',
            'prev_tag_close' => '</a></li>',
            'num_tag_open' => '<li class="page-item"><a class="page-link"',
            'num_tag_close' => '</a></li>',
        ];
    }
    
    protected function renderActive(LinkData $link): string
    {
        return $this->config['cur_tag_open'] . $link->getText() . $this->config['cur_tag_close'];
    }
    
    protected function renderAnchor(LinkData $link): string
    {
        $open = '';
        $close = '';
        
        if ($link->isFirst()) {
            $open = $this->config['first_tag_open'] . $this->buildAttributes($link) . '>';
            $close = $this->config['first_tag_close'];
        } elseif ($link->isLast()) {
            $open = $this->config['last_tag_open'] . $this->buildAttributes($link) . '>';
            $close = $this->config['last_tag_close'];
        } elseif ($link->isPrevious()) {
            $open = $this->config['prev_tag_open'] . $this->buildAttributes($link) . '>';
            $close = $this->config['prev_tag_close'];
        } elseif ($link->isNext()) {
            $open = $this->config['next_tag_open'] . $this->buildAttributes($link) . '>';
            $close = $this->config['next_tag_close'];
        } else {
            $open = $this->config['num_tag_open'] . $this->buildAttributes($link) . '>';
            $close = $this->config['num_tag_close'];
        }
        
        return $open . $link->getText() . $close;
    }
}
