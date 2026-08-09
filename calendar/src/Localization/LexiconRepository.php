<?php

declare(strict_types=1);

namespace Kodhe\Framework\Calendar\Localization;

/**
 * Class LexiconRepository
 *
 * Repository for localized calendar lexicons with lazy loading
 *
 * @package Kodhe\Calendar\Localization
 */
class LexiconRepository
{
    /**
     * Singleton instance
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Loaded lexicons cache
     *
     * @var array
     */
    private $lexicons = [];

    /**
     * Get singleton instance
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get month name
     *
     * @param int $month
     * @param string $locale
     * @param string $type
     * @return string
     */
    public function monthName(int $month, string $locale = 'en', string $type = 'long'): string
    {
        $lexicon = $this->load($locale);
        $months = $lexicon->months($type);
        
        return $months[$month - 1] ?? '';
    }

    /**
     * Get day names
     *
     * @param string $type
     * @param string $locale
     * @return array
     */
    public function dayNames(string $type = 'abr', string $locale = 'en'): array
    {
        $lexicon = $this->load($locale);
        return $lexicon->days($type);
    }

    /**
     * Load lexicon (lazy loading with cache)
     *
     * @param string $locale
     * @return LocalLexicon
     */
    private function load(string $locale): LocalLexicon
    {
        if (!isset($this->lexicons[$locale])) {
            $this->lexicons[$locale] = new LocalLexicon($locale);
        }

        return $this->lexicons[$locale];
    }

    /**
     * Clear loaded lexicons
     *
     * @return void
     */
    public function clear(): void
    {
        $this->lexicons = [];
    }
}
