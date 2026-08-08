<?php

declare(strict_types=0);

namespace Kodhe\Framework\Pagination\Renderers;

use Kodhe\Framework\Pagination\Contracts\RendererInterface;
use Kodhe\Framework\Pagination\ValueObjects\LinkData;

/**
 * Default HTML Renderer
 * 
 * Renders pagination using standard HTML links
 */
class DefaultRenderer implements RendererInterface
{
    protected array $config = [];
    
    public function render(array $links): string
    {
        $output = '';
        
        foreach ($links as $link) {
            if (!$link instanceof LinkData) {
                continue;
            }
            
            $output .= $this->renderLink($link);
        }
        
        return $output;
    }
    
    protected function renderLink(LinkData $link): string
    {
        if ($link->isActive()) {
            return $this->renderActive($link);
        }
        
        return $this->renderAnchor($link);
    }
    
    protected function renderActive(LinkData $link): string
    {
        $open = $this->config['cur_tag_open'] ?? '<strong>';
        $close = $this->config['cur_tag_close'] ?? '</strong>';
        
        return $open . $link->getText() . $close;
    }
    
    protected function renderAnchor(LinkData $link): string
    {
        $open = $this->config['num_tag_open'] ?? '';
        $close = $this->config['num_tag_close'] ?? '';
        
        $attributes = $this->buildAttributes($link);
        
        return $open . '<a href="' . $link->getUrl() . '"' . $attributes . '>' 
            . $link->getText() . '</a>' . $close;
    }
    
    protected function buildAttributes(LinkData $link): string
    {
        $attrs = '';
        
        foreach ($link->getAttributes() as $key => $value) {
            $attrs .= ' ' . $key . '="' . $value . '"';
        }
        
        return $attrs;
    }
    
    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }
    
    public function getConfig(): array
    {
        return $this->config;
    }
}
