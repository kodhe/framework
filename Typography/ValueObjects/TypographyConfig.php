<?php

namespace Kodhe\Typography\ValueObjects;

/**
 * Value Object untuk konfigurasi Typography.
 */
class TypographyConfig
{
    /**
     * @var bool Apakah mengurangi line breaks
     */
    private bool $reduceLinebreaks;

    /**
     * @var bool Apakah mengaktifkan smart quotes
     */
    private bool $smartQuotes;

    /**
     * @var bool Apakah mengaktifkan character formatting
     */
    private bool $characterFormatting;

    /**
     * Constructor.
     *
     * @param bool $reduceLinebreaks
     * @param bool $smartQuotes
     * @param bool $characterFormatting
     */
    public function __construct(
        bool $reduceLinebreaks = false,
        bool $smartQuotes = true,
        bool $characterFormatting = true
    ) {
        $this->reduceLinebreaks = $reduceLinebreaks;
        $this->smartQuotes = $smartQuotes;
        $this->characterFormatting = $characterFormatting;
    }

    /**
     * Dapatkan status reduce line breaks.
     */
    public function shouldReduceLinebreaks(): bool
    {
        return $this->reduceLinebreaks;
    }

    /**
     * Dapatkan status smart quotes.
     */
    public function shouldUseSmartQuotes(): bool
    {
        return $this->smartQuotes;
    }

    /**
     * Dapatkan status character formatting.
     */
    public function shouldFormatCharacters(): bool
    {
        return $this->characterFormatting;
    }

    /**
     * Buat config dari array.
     *
     * @param array $config
     * @return self
     */
    public static function fromArray(array $config): self
    {
        return new self(
            $config['reduce_linebreaks'] ?? false,
            $config['smart_quotes'] ?? true,
            $config['character_formatting'] ?? true
        );
    }
}
