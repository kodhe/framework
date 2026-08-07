<?php

namespace Kodhe\Calendar\Localization;

/**
 * Class LocalLexicon
 *
 * Provides localized day and month names
 *
 * @package     Kodhe\Calendar
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class LocalLexicon
{
    /**
     * Locale code
     *
     * @var string
     */
    private $locale;

    /**
     * Day names data
     *
     * @var array
     */
    private static $days = [
        'en' => [
            'long'  => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            'short' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            'abr'   => ['S', 'M', 'T', 'W', 'T', 'F', 'S'],
        ],
        'id' => [
            'long'  => ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
            'short' => ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
            'abr'   => ['Mg', 'Sn', 'Sl', 'Rb', 'Km', 'Jm', 'Sb'],
        ],
        'fr' => [
            'long'  => ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
            'short' => ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
            'abr'   => ['D', 'L', 'M', 'M', 'J', 'V', 'S'],
        ],
        'de' => [
            'long'  => ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'],
            'short' => ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
            'abr'   => ['S', 'M', 'D', 'M', 'D', 'F', 'S'],
        ],
        'es' => [
            'long'  => ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
            'short' => ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'],
            'abr'   => ['D', 'L', 'M', 'X', 'J', 'V', 'S'],
        ],
    ];

    /**
     * Month names data
     *
     * @var array
     */
    private static $months = [
        'en' => [
            'long'  => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            'short' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ],
        'id' => [
            'long'  => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            'short' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
        ],
        'fr' => [
            'long'  => ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
            'short' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
        ],
        'de' => [
            'long'  => ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],
            'short' => ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'],
        ],
        'es' => [
            'long'  => ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
            'short' => ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
        ],
    ];

    /**
     * Constructor
     *
     * @param string $locale
     */
    public function __construct(string $locale = 'en')
    {
        $this->locale = $locale;
    }

    /**
     * Get day names array
     *
     * @param string $type 'long', 'short', or 'abr'
     * @return array
     */
    public function days(string $type = 'long'): array
    {
        $type = strtolower($type);

        if (!isset(self::$days[$this->locale][$type])) {
            // Fallback to English if locale not found
            return self::$days['en'][$type] ?? self::$days['en']['long'];
        }

        return self::$days[$this->locale][$type];
    }

    /**
     * Get month names array
     *
     * @param string $type 'long' or 'short'
     * @return array
     */
    public function months(string $type = 'long'): array
    {
        $type = strtolower($type);

        if (!isset(self::$months[$this->locale][$type])) {
            // Fallback to English if locale not found
            return self::$months['en'][$type] ?? self::$months['en']['long'];
        }

        return self::$months[$this->locale][$type];
    }

    /**
     * Get single day name
     *
     * @param int    $index Day index (0-6)
     * @param string $type  'long', 'short', or 'abr'
     * @return string
     */
    public function dayName(int $index, string $type = 'long'): string
    {
        $days = $this->days($type);
        return $days[$index] ?? '';
    }

    /**
     * Get single month name
     *
     * @param int    $index Month index (1-12, 1-based)
     * @param string $type  'long' or 'short'
     * @return string
     */
    public function monthName(int $index, string $type = 'long'): string
    {
        $months = $this->months($type);
        return $months[$index - 1] ?? '';
    }
}
