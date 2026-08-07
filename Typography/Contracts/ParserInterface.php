<?php

namespace Kodhe\Typography\Contracts;

/**
 * Interface untuk parser HTML dan teks.
 */
interface ParserInterface
{
    /**
     * Parse konten HTML atau teks.
     *
     * @param string $content Konten yang akan diparse
     * @return array Hasil parsing
     */
    public function parse(string $content): array;

    /**
     * Restore konten yang sudah diproses.
     *
     * @param string $content Konten yang akan direstore
     * @param array $data Data hasil parsing
     * @return string Konten yang sudah direstore
     */
    public function restore(string $content, array $data): string;
}
