<?php

declare(strict_types=1);

namespace Kodhe\Framework\Agent\Collections;

/**
 * Class RobotCollection
 * 
 * Collection of robot/crawler data for detection
 * 
 * @package Kodhe\Agent\Collections
 * @author  Your Name
 * @version 2.0.0
 */
class RobotCollection
{
    /**
     * List of robots to compare against current user agent
     *
     * @var array
     */
    protected array $robots = [
        'Googlebot' => 'Googlebot',
        'GoogleBot-Mobile' => 'GoogleBot-Mobile',
        'Mediapartners-Google' => 'Mediapartners-Google',
        'AdsBot-Google' => 'AdsBot-Google',
        'Feedfetcher-Google' => 'Feedfetcher-Google',
        'MSNBot' => 'MSNBot',
        'msnbot-media' => 'MSNBot-Media',
        'Slurp' => 'Yahoo! Slurp',
        'Baiduspider' => 'Baiduspider',
        'YandexBot' => 'YandexBot',
        'YandexImages' => 'YandexImages',
        'facebookexternalhit' => 'FacebookExternalHit',
        'Twitterbot' => 'Twitterbot',
        'LinkedInBot' => 'LinkedInBot',
        'ia_archiver' => 'Alexa Crawler',
        'MJ12bot' => 'MJ12bot',
        'AhrefsBot' => 'AhrefsBot',
        'SemrushBot' => 'SemrushBot',
        'DotBot' => 'DotBot',
        'rogerbot' => 'Rogerbot',
        'Exabot' => 'Exabot',
        'facebot' => 'Facebot',
        'flipboard' => 'Flipboard',
        'Applebot' => 'Applebot',
        'bingbot' => 'Bingbot',
        'Sogou' => 'Sogou',
        'Ezooms' => 'Ezooms',
        'Yahoo! Slurp' => 'Yahoo! Slurp',
        'DuckDuckBot' => 'DuckDuckBot',
        'PetalBot' => 'PetalBot',
        'BLEXBot' => 'BLEXBot',
        'SeekportBot' => 'SeekportBot',
        'ZoominfoBot' => 'ZoominfoBot',
        'MegaIndex' => 'MegaIndex',
        'SEMrushBot' => 'SEMrushBot',
        'DataForSeoBot' => 'DataForSeoBot',
        'VelenPublicWebCrawler' => 'VelenPublicWebCrawler',
        'Cliqzbot' => 'Cliqzbot',
        'Qwantify' => 'Qwantify',
        'Lipperhey' => 'Lipperhey',
        'yacybot' => 'YaCy',
        'archive.org_bot' => 'Archive.org Bot',
        'Wayback Machine' => 'Wayback Machine',
        'Screaming Frog' => 'Screaming Frog',
        'RyteBot' => 'RyteBot',
        'UptimeRobot' => 'UptimeRobot',
        'Pingdom.com_bot' => 'Pingdom Bot',
        'Site24x7' => 'Site24x7',
        'Jetty' => 'Jetty',
        'Gigablast' => 'Gigablast',
        'Cuil' => 'Cuil',
        'Findxbot' => 'Findxbot',
        'CommonCrawler' => 'CommonCrawler',
        'MetaJobBot' => 'MetaJobBot',
        'Nutch' => 'Nutch',
        'Scrapy' => 'Scrapy',
        'curl' => 'cURL',
        'Wget' => 'Wget',
        'libwww-perl' => 'libwww-perl',
        'Python-urllib' => 'Python-urllib',
        'python-requests' => 'Python Requests',
        'Go-http-client' => 'Go HTTP Client',
        'Java' => 'Java',
        'Apache-HttpClient' => 'Apache HttpClient',
        'okhttp' => 'OkHttp',
        'axios' => 'Axios',
        'node-fetch' => 'Node Fetch',
        'PostmanRuntime' => 'Postman',
        'Insomnia' => 'Insomnia',
    ];

    /**
     * Get all robots
     *
     * @return array
     */
    public function all(): array
    {
        return $this->robots;
    }

    /**
     * Get a specific robot name by key
     *
     * @param string $key Robot key
     * @return string|null
     */
    public function get(string $key): ?string
    {
        return $this->robots[$key] ?? null;
    }

    /**
     * Check if a robot key exists
     *
     * @param string $key Robot key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->robots[$key]);
    }

    /**
     * Set custom robots
     *
     * @param array $robots Custom robots array
     * @return void
     */
    public function set(array $robots): void
    {
        $this->robots = $robots;
    }

    /**
     * Add a robot to the collection
     *
     * @param string $key Robot key/pattern
     * @param string $name Robot display name
     * @return void
     */
    public function add(string $key, string $name): void
    {
        $this->robots[$key] = $name;
    }
}
