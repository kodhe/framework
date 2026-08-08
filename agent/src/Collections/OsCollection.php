<?php

declare(strict_types=1);

namespace Kodhe\Framework\Agent\Collections;

/**
 * Class OsCollection
 * 
 * Collection of operating system/platform data for detection
 * 
 * @package Kodhe\Agent\Collections
 * @author  Your Name
 * @version 2.0.0
 */
class OsCollection
{
    /**
     * List of platforms to compare against current user agent
     *
     * @var array
     */
    protected array $platforms = [
        'Windows NT 10.0' => 'Windows 10',
        'Windows NT 6.3' => 'Windows 8.1',
        'Windows NT 6.2' => 'Windows 8',
        'Windows NT 6.1' => 'Windows 7',
        'Windows NT 6.0' => 'Windows Vista',
        'Windows NT 5.2' => 'Windows 2003',
        'Windows NT 5.1' => 'Windows XP',
        'Windows NT 5.0' => 'Windows 2000',
        'Windows NT 4.0' => 'Windows NT 4.0',
        'WinNT4.0' => 'Windows NT 4.0',
        'WinNT 4.0' => 'Windows NT',
        'Windows ME' => 'Windows ME',
        'Win98' => 'Windows 98',
        'Win95' => 'Windows 95',
        'Win16' => 'Windows 3.11',
        'Mac OS X 10[._]1[0-9]' => 'Mac OS X Cheetah',
        'Mac OS X 10[._]1[1-9]' => 'Mac OS X Puma',
        'Mac OS X 10[._][2-9]' => 'Mac OS X Jaguar',
        'Mac OS X 10[._]1[0-4]' => 'Mac OS X Panther',
        'Mac OS X 10[._]5' => 'Mac OS X Leopard',
        'Mac OS X 10[._]6' => 'Mac OS X Snow Leopard',
        'Mac OS X 10[._]7' => 'Mac OS X Lion',
        'Mac OS X 10[._]8' => 'Mac OS X Mountain Lion',
        'Mac OS X 10[._]9' => 'Mac OS X Mavericks',
        'Mac OS X 10[._]1[0-9]' => 'Mac OS X Yosemite',
        'Mac OS X 10[._]1[1-9]' => 'Mac OS X El Capitan',
        'Mac OS X 10[._]1[2-9]' => 'macOS Sierra',
        'Mac OS X 10[._]1[3-9]' => 'macOS High Sierra',
        'Mac OS X 10[._]1[4-9]' => 'macOS Mojave',
        'Mac OS X 10[._]1[5-9]' => 'macOS Catalina',
        'Mac OS X 10[._]1[6-9]' => 'macOS Big Sur',
        'Mac OS X 10[._]1[7-9]' => 'macOS Monterey',
        'Mac OS X 10[._]1[8-9]' => 'macOS Ventura',
        'Mac OS X 10[._]1[9-9]' => 'macOS Sonoma',
        'Mac OS' => 'Mac OS',
        'Mac_PowerPC' => 'Mac OS',
        'Macintosh' => 'Mac OS',
        'Linux' => 'Linux',
        'Ubuntu' => 'Ubuntu',
        'Debian' => 'Debian',
        'Fedora' => 'Fedora',
        'Gentoo' => 'Gentoo',
        'CentOS' => 'CentOS',
        'Red Hat' => 'Red Hat',
        'SuSE' => 'SuSE',
        'Android' => 'Android',
        'BlackBerry' => 'BlackBerry',
        'OpenBSD' => 'OpenBSD',
        'NetBSD' => 'NetBSD',
        'FreeBSD' => 'FreeBSD',
        'SunOS' => 'SunOS',
        'AIX' => 'AIX',
        'BeOS' => 'BeOS',
        'OS/2' => 'OS/2',
        'Symbian' => 'Symbian',
        'Java' => 'Java',
        'COM' => 'COM',
        'CFNetwork' => 'iOS',
        'J2ME' => 'J2ME',
        'iPhone' => 'iOS',
        'iPad' => 'iOS',
        'iPod' => 'iOS',
        'webOS' => 'webOS',
        'Windows Phone' => 'Windows Phone',
        'PlayStation' => 'PlayStation',
        'Xbox' => 'Xbox',
        'Nintendo' => 'Nintendo',
        'Chrome OS' => 'Chrome OS',
        'CrOS' => 'Chrome OS',
    ];

    /**
     * Get all platforms
     *
     * @return array
     */
    public function all(): array
    {
        return $this->platforms;
    }

    /**
     * Get a specific platform name by key
     *
     * @param string $key Platform key
     * @return string|null
     */
    public function get(string $key): ?string
    {
        return $this->platforms[$key] ?? null;
    }

    /**
     * Check if a platform key exists
     *
     * @param string $key Platform key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->platforms[$key]);
    }

    /**
     * Set custom platforms
     *
     * @param array $platforms Custom platforms array
     * @return void
     */
    public function set(array $platforms): void
    {
        $this->platforms = $platforms;
    }

    /**
     * Add a platform to the collection
     *
     * @param string $key Platform key/pattern
     * @param string $name Platform display name
     * @return void
     */
    public function add(string $key, string $name): void
    {
        $this->platforms[$key] = $name;
    }
}
