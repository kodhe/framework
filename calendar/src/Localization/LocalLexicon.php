<?php

declare(strict_types=0);

namespace Kodhe\Framework\Calendar\Localization;

/**
 * Class LocalLexicon
 *
 * Provides localized day and month names
 *
 * @package Kodhe\Calendar\Localization
 */
class LocalLexicon
{
    /**
     * Available locales
     *
     * @var array
     */
    private static $locales = ['en', 'id', 'fr', 'de', 'es'];

    /**
     * Current locale
     *
     * @var string
     */
    private $locale;

    /**
     * Constructor
     *
     * @param string $locale
     */
    public function __construct(string $locale = 'en')
    {
        $this->locale = in_array($locale, self::$locales) ? $locale : 'en';
    }

    /**
     * Get month names
     *
     * @param string $type short|long
     * @return array
     */
    public function months(string $type = 'long'): array
    {
        $data = $this->load();
        return $data['months'][$type] ?? $data['months']['long'];
    }

    /**
     * Get day names
     *
     * @param string $type long|short|abr
     * @return array
     */
    public function days(string $type = 'abr'): array
    {
        $data = $this->load();
        return $data['days'][$type] ?? $data['days']['abr'];
    }

    /**
     * Load lexicon data
     *
     * @return array
     */
    private function load(): array
    {
        $lexicons = [
            'en' => [
                'months' => [
                    'short' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    'long' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                ],
                'days' => [
                    'long' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                    'short' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                    'abr' => ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
                ],
            ],
            'id' => [
                'months' => [
                    'short' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                    'long' => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                ],
                'days' => [
                    'long' => ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
                    'short' => ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                    'abr' => ['Mi', 'Se', 'Se', 'Ra', 'Ka', 'Ju', 'Sa'],
                ],
            ],
            'fr' => [
                'months' => [
                    'short' => ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'],
                    'long' => ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'],
                ],
                'days' => [
                    'long' => ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'],
                    'short' => ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'],
                    'abr' => ['D', 'L', 'M', 'M', 'J', 'V', 'S'],
                ],
            ],
            'de' => [
                'months' => [
                    'short' => ['Jan.', 'Feb.', 'März', 'Apr.', 'Mai', 'Juni', 'Juli', 'Aug.', 'Sep.', 'Okt.', 'Nov.', 'Dez.'],
                    'long' => ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],
                ],
                'days' => [
                    'long' => ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'],
                    'short' => ['So.', 'Mo.', 'Di.', 'Mi.', 'Do.', 'Fr.', 'Sa.'],
                    'abr' => ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
                ],
            ],
            'es' => [
                'months' => [
                    'short' => ['ene.', 'feb.', 'mar.', 'abr.', 'may.', 'jun.', 'jul.', 'ago.', 'sep.', 'oct.', 'nov.', 'dic.'],
                    'long' => ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'],
                ],
                'days' => [
                    'long' => ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'],
                    'short' => ['dom.', 'lun.', 'mar.', 'mié.', 'jue.', 'vie.', 'sáb.'],
                    'abr' => ['D', 'L', 'M', 'X', 'J', 'V', 'S'],
                ],
            ],
        ];

        return $lexicons[$this->locale] ?? $lexicons['en'];
    }
}
