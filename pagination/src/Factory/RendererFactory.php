<?php
namespace Kodhe\Pagination\Factory;

use Kodhe\Pagination\Contracts\RendererInterface;
use Kodhe\Pagination\Pagination;
use Kodhe\Pagination\Renderers\DefaultRenderer;
use Kodhe\Pagination\Renderers\BootstrapRenderer;
use Kodhe\Pagination\Renderers\TailwindRenderer;

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