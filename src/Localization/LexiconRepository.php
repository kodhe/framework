<?php

namespace Kodhe\Calendar\Localization;

/**
 * Class LexiconRepository
 *
 * Repository for managing locale lexicons with lazy loading
 *
 * @package     Kodhe\Calendar
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class LexiconRepository
{
    /**
     * Cached lexicon instances
     *
     * @var array|LocalLexicon[]
     */
    private static $instances = [];

    /**
     * Default locale
     *
     * @var string
     */
    private $defaultLocale = 'en';

    /**
     * Get month name by month number
     *
     * @param int    $month  Month number (1-12)
     * @param string $locale Locale code
     * @param string $type   'long' or 'short'
     * @return string
     */
    public function monthName(int $month, string $locale = 'en', string $type = 'long'): string
    {
        $lexicon = $this->load($locale);
        return $lexicon->monthName($month, $type);
    }

    /**
     * Get day names array
     *
     * @param string $type   'long', 'short', or 'abr'
     * @param string $locale Locale code
     * @return array
     */
    public function dayNames(string $type = 'abr', string $locale = 'en'): array
    {
        $lexicon = $this->load($locale);
        return $lexicon->days($type);
    }

    /**
     * Get single day name
     *
     * @param int    $index  Day index (0-6)
     * @param string $type   'long', 'short', or 'abr'
     * @param string $locale Locale code
     * @return string
     */
    public function dayName(int $index, string $type = 'long', string $locale = 'en'): string
    {
        $lexicon = $this->load($locale);
        return $lexicon->dayName($index, $type);
    }

    /**
     * Load lexicon instance with lazy loading and singleton pattern
     *
     * @param string $locale
     * @return LocalLexicon
     */
    private function load(string $locale): LocalLexicon
    {
        $key = strtolower($locale);

        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new LocalLexicon($locale);
        }

        return self::$instances[$key];
    }

    /**
     * Set default locale
     *
     * @param string $locale
     * @return self
     */
    public function setDefaultLocale(string $locale): self
    {
        $this->defaultLocale = $locale;
        return $this;
    }

    /**
     * Get default locale
     *
     * @return string
     */
    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    /**
     * Clear cached lexicon instances
     *
     * @return void
     */
    public function clearCache(): void
    {
        self::$instances = [];
    }

    /**
     * Check if locale is available
     *
     * @param string $locale
     * @return bool
     */
    public function hasLocale(string $locale): bool
    {
        // Check if locale exists in LocalLexicon static data
        $reflection = new \ReflectionClass(LocalLexicon::class);
        $daysProperty = $reflection->getProperty('days');
        $daysProperty->setAccessible(true);
        $days = $daysProperty->getValue();

        return isset($days[$locale]);
    }

    /**
     * Get available locales
     *
     * @return array
     */
    public function getAvailableLocales(): array
    {
        $reflection = new \ReflectionClass(LocalLexicon::class);
        $daysProperty = $reflection->getProperty('days');
        $daysProperty->setAccessible(true);
        $days = $daysProperty->getValue();

        return array_keys($days);
    }
}
