<?php

namespace Kodhe\Typography\Contracts;

/**
 * Interface untuk formatter teks dalam pipeline typography.
 */
interface FormatterInterface
{
    /**
     * Format teks sesuai dengan strategi formatter.
     *
     * @param string $text Teks yang akan diformat
     * @return string Teks yang sudah diformat
     */
    public function format(string $text): string;
}
