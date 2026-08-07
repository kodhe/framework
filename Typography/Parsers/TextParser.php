<?php

namespace Kodhe\Typography\Parsers;

use Kodhe\Typography\Contracts\ParserInterface;

/**
 * Parser untuk teks biasa.
 */
class TextParser implements ParserInterface
{
    /**
     * Parse konten teks.
     */
    public function parse(string $content): array
    {
        return [
            'content' => $content,
            'metadata' => [
                'length' => strlen($content),
                'lines' => substr_count($content, "\n")
            ]
        ];
    }

    /**
     * Restore konten teks (tidak ada perubahan).
     */
    public function restore(string $content, array $data): string
    {
        return $content;
    }
}
