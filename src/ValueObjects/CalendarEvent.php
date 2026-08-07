<?php

namespace Kodhe\Calendar\ValueObjects;

/**
 * Class CalendarEvent
 *
 * Value object representing a calendar event
 *
 * @package     Kodhe\Calendar
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
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
     * Event URL (optional)
     *
     * @var string|null
     */
    private $url;

    /**
     * Event description (optional)
     *
     * @var string|null
     */
    private $description;

    /**
     * Additional event data (optional)
     *
     * @var array
     */
    private $data;

    /**
     * Constructor
     *
     * @param string      $title       Event title
     * @param string|null $url         Event URL
     * @param string|null $description Event description
     * @param array       $data        Additional data
     */
    public function __construct(
        string $title,
        ?string $url = null,
        ?string $description = null,
        array $data = []
    ) {
        $this->title       = $title;
        $this->url         = $url;
        $this->description = $description;
        $this->data        = $data;
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
     * Get specific data value
     *
     * @param string $key     Data key
     * @param mixed  $default Default value if key not found
     * @return mixed
     */
    public function getDataValue(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Check if event has URL
     *
     * @return bool
     */
    public function hasUrl(): bool
    {
        return $this->url !== null;
    }

    /**
     * Check if event has description
     *
     * @return bool
     */
    public function hasDescription(): bool
    {
        return $this->description !== null;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        $array = [
            'title' => $this->title,
        ];

        if ($this->url !== null) {
            $array['url'] = $this->url;
        }

        if ($this->description !== null) {
            $array['description'] = $this->description;
        }

        if (!empty($this->data)) {
            $array['data'] = $this->data;
        }

        return $array;
    }

    /**
     * Create from array
     *
     * @param array $data Event data array
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['title'] ?? '',
            $data['url'] ?? null,
            $data['description'] ?? null,
            $data['data'] ?? []
        );
    }

    /**
     * Create from simple string (backward compatibility)
     *
     * @param string $title Event title/URL
     * @return self
     */
    public static function fromString(string $title): self
    {
        // If it looks like a URL, use it as URL with title as fallback
        if (filter_var($title, FILTER_VALIDATE_URL)) {
            return new self($title, $title);
        }

        return new self($title);
    }
}
