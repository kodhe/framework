<?php
namespace Kodhe\Framework\Pagination\Factory;

use Kodhe\Framework\Pagination\Contracts\RendererInterface;
use Kodhe\Framework\Pagination\Pagination;
use Kodhe\Framework\Pagination\Renderers\DefaultRenderer;
use Kodhe\Framework\Pagination\Renderers\BootstrapRenderer;
use Kodhe\Framework\Pagination\Renderers\TailwindRenderer;

class RendererFactory
{
    public static function make(string $type, Pagination $context): RendererInterface
    {
        return match($type) {
            'bootstrap' => new BootstrapRenderer($context),
            'tailwind' => new TailwindRenderer($context),
            default => new DefaultRenderer($context),
        };
    }
}