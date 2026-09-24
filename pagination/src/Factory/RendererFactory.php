<?php

declare(strict_types=1);

namespace Kodhe\Framework\Pagination\Factory;

use Kodhe\Framework\Pagination\Contracts\RendererInterface;
use Kodhe\Framework\Pagination\Pagination;
use Kodhe\Framework\Pagination\Renderers\DefaultRenderer;
use Kodhe\Framework\Pagination\Renderers\BootstrapRenderer;
use Kodhe\Framework\Pagination\Renderers\TailwindRenderer;

/**
 * Factory untuk renderer pagination.
 *
 * Catatan perbaikan (PR-11): pemanggilan lama `new DefaultRenderer($context)`
 * adalah TypeError (constructor tanpa parameter), sementara Bootstrap/Tailwind
 * dipanggil tanpa argumen padahal menerima $context -> ArgumentCountError.
 * Kini seragam: semua renderer dibuat tanpa argumen, lalu tag konfigurasi
 * dari objek Pagination disuntik via setConfig() milik RendererInterface.
 */
class RendererFactory
{
    public static function make(string $type, ?Pagination $context = null): RendererInterface
    {
        $renderer = match ($type) {
            'bootstrap' => new BootstrapRenderer(),
            'tailwind'  => new TailwindRenderer(),
            default     => new DefaultRenderer(),
        };

        if ($context !== null) {
            $config = [];
            foreach (get_object_vars($context) as $key => $value) {
                if (is_string($key)
                    && (str_ends_with($key, '_tag_open') || str_ends_with($key, '_tag_close'))
                    && is_scalar($value)) {
                    $config[$key] = (string) $value;
                }
            }
            if ($config !== []) {
                $renderer->setConfig($config);
            }
        }

        return $renderer;
    }
}
