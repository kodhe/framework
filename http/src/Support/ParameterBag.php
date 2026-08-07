<?php

declare(strict_types=1);

namespace CodeIgniter\Http\Support;

class ParameterBag
{
    protected array $parameters = [];

    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    public function get(string $key, $default = null)
    {
        return $this->parameters[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $this->parameters[$key] = $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    public function all(): array
    {
        return $this->parameters;
    }
}
