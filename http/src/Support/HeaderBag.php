<?php

declare(strict_types=1);

namespace CodeIgniter\Http\Support;

/**
 * Header Bag - Collection of HTTP headers
 */
class HeaderBag
{
    protected array $headers = [];

    public function __construct(array $headers = [])
    {
        foreach ($headers as $name => $value) {
            $this->set($name, $value);
        }
    }

    public function get(string $name): array
    {
        $lowerName = strtolower($name);
        return $this->headers[$lowerName] ?? [];
    }

    public function set(string $name, $value): void
    {
        $lowerName = strtolower($name);
        $this->headers[$lowerName] = is_array($value) ? $value : [$value];
    }

    public function add(string $name, $value): void
    {
        $lowerName = strtolower($name);
        
        if (!isset($this->headers[$lowerName])) {
            $this->headers[$lowerName] = [];
        }
        
        $values = is_array($value) ? $value : [$value];
        $this->headers[$lowerName] = array_merge($this->headers[$lowerName], $values);
    }

    public function has(string $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    public function remove(string $name): void
    {
        unset($this->headers[strtolower($name)]);
    }

    public function all(): array
    {
        return $this->headers;
    }

    public function clear(): void
    {
        $this->headers = [];
    }

    public function count(): int
    {
        return count($this->headers);
    }
}
