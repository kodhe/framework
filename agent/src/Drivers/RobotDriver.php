<?php

declare(strict_types=1);

namespace Kodhe\Framework\Agent\Drivers;

use Kodhe\Framework\Agent\Contracts\AgentDriverInterface;
use Kodhe\Framework\Agent\Parsers\UserAgentParser;
use Kodhe\Framework\Agent\Collections\RobotCollection;

/**
 * Class RobotDriver
 * 
 * Handles robot/crawler detection from user agent strings
 * 
 * @package Kodhe\Agent\Drivers
 * @author  Your Name
 * @version 2.0.0
 */
class RobotDriver implements AgentDriverInterface
{
    /**
     * User agent parser instance
     *
     * @var UserAgentParser
     */
    protected UserAgentParser $parser;

    /**
     * Robot collection instance
     *
     * @var RobotCollection
     */
    protected RobotCollection $collection;

    /**
     * Detected robot name
     *
     * @var string
     */
    protected string $robot = '';

    /**
     * Flag indicating if agent is a robot
     *
     * @var bool
     */
    protected bool $isRobot = false;

    /**
     * Constructor
     *
     * @param UserAgentParser $parser     User agent parser
     * @param RobotCollection $collection Robot collection
     */
    public function __construct(
        UserAgentParser $parser,
        ?RobotCollection $collection = null
    ) {
        $this->parser = $parser;
        $this->collection = $collection ?? new RobotCollection();
        $this->detect();
    }

    /**
     * Detect the robot from user agent
     *
     * @return void
     */
    public function detect(): void
    {
        $this->robot = '';
        $this->isRobot = false;

        $robots = $this->collection->all();

        if (empty($robots)) {
            return;
        }

        foreach ($robots as $key => $name) {
            // Use regex matching for robots
            if ($this->parser->match('|' . preg_quote((string) $key) . '|i')) {
                $this->isRobot = true;
                $this->robot = $name;
                return;
            }
        }
    }

    /**
     * Get the detected robot name
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->robot;
    }

    /**
     * Get the robot name
     *
     * @return string
     */
    public function getRobot(): string
    {
        return $this->robot;
    }

    /**
     * Check if the agent is a robot
     *
     * @param string|null $key Optional specific robot key to check
     * @return bool
     */
    public function isMatch(?string $key = null): bool
    {
        if (!$this->isRobot) {
            return false;
        }

        if ($key === null) {
            return true;
        }

        $robotName = $this->collection->get($key);
        return $robotName !== null && $this->robot === $robotName;
    }

    /**
     * Check if agent is a robot/crawler
     *
     * @return bool
     */
    public function isRobot(): bool
    {
        return $this->isRobot;
    }

    /**
     * Check if agent is a search engine bot
     *
     * @return bool
     */
    public function isSearchEngine(): bool
    {
        $searchEngines = ['Googlebot', 'Bingbot', 'Yahoo', 'Baidu', 'Yandex', 'DuckDuck'];
        
        foreach ($searchEngines as $engine) {
            if (stripos($this->robot, $engine) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if agent is a social media bot
     *
     * @return bool
     */
    public function isSocialMedia(): bool
    {
        $socialBots = ['facebook', 'Twitter', 'LinkedIn', 'Pinterest'];
        
        foreach ($socialBots as $bot) {
            if (stripos($this->robot, $bot) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if agent is an SEO/analysis tool
     *
     * @return bool
     */
    public function isSeoTool(): bool
    {
        $seoTools = ['Ahrefs', 'Semrush', 'MJ12', 'DotBot', 'BLEX', 'Screaming Frog'];
        
        foreach ($seoTools as $tool) {
            if (stripos($this->robot, $tool) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get robot collection
     *
     * @return RobotCollection
     */
    public function getCollection(): RobotCollection
    {
        return $this->collection;
    }

    /**
     * Set robot collection
     *
     * @param RobotCollection $collection Robot collection
     * @return void
     */
    public function setCollection(RobotCollection $collection): void
    {
        $this->collection = $collection;
        $this->detect();
    }
}
