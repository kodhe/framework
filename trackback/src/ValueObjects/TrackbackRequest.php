<?php

declare(strict_types=1);

namespace Kodhe\Framework\Trackback\ValueObjects;

/**
 * Value object representing a trackback request.
 */
class TrackbackRequest
{
    private string $url;
    private string $title;
    private string $excerpt;
    private string $blogName;
    private string $charset;

    public function __construct(
        string $url,
        string $title,
        string $excerpt,
        string $blogName,
        string $charset = 'UTF-8'
    ) {
        $this->url = $url;
        $this->title = $title;
        $this->excerpt = $excerpt;
        $this->blogName = $blogName;
        $this->charset = $charset;
    }

    /**
     * Create from array data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['url'] ?? '',
            $data['title'] ?? '',
            $data['excerpt'] ?? '',
            $data['blog_name'] ?? '',
            $data['charset'] ?? 'UTF-8'
        );
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getExcerpt(): string
    {
        return $this->excerpt;
    }

    public function getBlogName(): string
    {
        return $this->blogName;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * Convert to array for POST data.
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'blog_name' => $this->blogName,
            'charset' => $this->charset,
        ];
    }

    /**
     * Check if all required fields are present.
     */
    public function isValid(): bool
    {
        return !empty($this->url) 
            && !empty($this->title) 
            && !empty($this->excerpt) 
            && !empty($this->blogName);
    }
}
