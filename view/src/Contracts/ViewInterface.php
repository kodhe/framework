<?php

namespace Kodhe\Framework\View\Contracts;

/**
 * Interface ViewInterface
 *
 * @package Kodhe\Framework\View\Contracts
 */
interface ViewInterface
{
    /**
     * Set the view name
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name): self;

    /**
     * Get the view name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Set view data
     *
     * @param array $data
     * @return self
     */
    public function setData(array $data): self;

    /**
     * Get view data
     *
     * @return array
     */
    public function getData(): array;

    /**
     * Set a single data item
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function set(string $key, $value): self;

    /**
     * Get a single data item
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Render the view
     *
     * @param bool $return
     * @return string|void
     */
    public function render(bool $return = true);

    /**
     * Check if view exists
     *
     * @return bool
     */
    public function exists(): bool;
}
