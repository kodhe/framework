<?php

declare(strict_types=1);

namespace Kodhe\Framework\Calendar\ValueObjects;

/**
 * Class CalendarEvent
 *
 * Value object representing a calendar event
 *
 * @package Kodhe\Calendar\ValueObjects
 */
class CalendarEvent
{
    /**
     * Event title
     *
     * @var string
     */
    private $title;

    /**
     * Event URL
     *
     * @var string|null
     */
    private $url;

    /**
     * Event description
     *
     * @var string|null
     */
    private $description;

    /**
     * Additional data
     *
     * @var array
     */
    private $data;

    /**
     * Constructor
     *
     * @param string $title
     * @param string|null $url
     * @param string|null $description
     * @param array $data
     */
    public function __construct(
        string $title,
        ?string $url = null,
        ?string $description = null,
        array $data = []
    ) {
        $this->title = $title;
        $this->url = $url;
        $this->description = $description;
        $this->data = $data;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get URL
     *
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Get description
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Get additional data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'url' => $this->url,
            'description' => $this->description,
            'data' => $this->data,
        ];
    }

    /**
     * Create from array
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['title'] ?? '',
            $data['url'] ?? null,
            $data['description'] ?? null,
            $data
        );
    }
}
