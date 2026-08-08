<?php

declare(strict_types=1);

namespace Kodhe\Framework\Pagination\Renderers;

use Kodhe\Framework\Pagination\ValueObjects\LinkData;

/**
 * Tailwind CSS Pagination Renderer
 */
class TailwindRenderer extends DefaultRenderer
{
    public function __construct()
    {
        $this->config = [
            'full_tag_open' => '<nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">',
            'full_tag_close' => '</nav>',
            'first_tag_open' => '',
            'first_tag_close' => '',
            'last_tag_open' => '',
            'last_tag_close' => '',
            'cur_tag_open' => '<span class="relative z-10 flex items-center bg-indigo-600 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">',
            'cur_tag_close' => '</span>',
            'next_tag_open' => '',
            'next_tag_close' => '',
            'prev_tag_open' => '',
            'prev_tag_close' => '',
            'num_tag_open' => '',
            'num_tag_close' => '',
        ];
    }
    
    protected function renderAnchor(LinkData $link): string
    {
        $baseClass = 'relative flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0';
        
        if ($link->isPrevious() || $link->isNext()) {
            return '<a href="' . $link->getUrl() . '" class="' . $baseClass . '">' 
                . $link->getText() . '</a>';
        }
        
        return '<a href="' . $link->getUrl() . '" class="' . $baseClass . '">' 
            . $link->getText() . '</a>';
    }
    
    protected function renderActive(LinkData $link): string
    {
        return $this->config['cur_tag_open'] . $link->getText() . $this->config['cur_tag_close'];
    }
}
