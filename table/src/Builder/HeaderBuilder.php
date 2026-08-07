<?php

declare(strict_types=1);

namespace Kodhe\Table\Builder;

use Kodhe\Table\Support\ColumnNormalizer;

/**
 * Builder for table header
 */
class HeaderBuilder
{
    /**
     * @var array The heading data
     */
    private array $heading = [];

    /**
     * @var ColumnNormalizer The column normalizer
     */
    private ColumnNormalizer $normalizer;

    /**
     * Constructor
     *
     * @param ColumnNormalizer|null $normalizer
     */
    public function __construct(?ColumnNormalizer $normalizer = null)
    {
        $this->normalizer = $normalizer ?? new ColumnNormalizer();
    }

    /**
     * Set heading from arguments
     *
     * @param mixed $args
     * @return self
     */
    public function setHeading($args = null): self
    {
        if ($args === null) {
            $this->heading = [];
            return $this;
        }

        $arguments = func_get_args();
        $this->heading = $this->normalizer->prepArgs($arguments);
        return $this;
    }

    /**
     * Get the heading
     *
     * @return array
     */
    public function getHeading(): array
    {
        return $this->heading;
    }

    /**
     * Check if heading is empty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->heading);
    }

    /**
     * Clear the heading
     *
     * @return self
     */
    public function clear(): self
    {
        $this->heading = [];
        return $this;
    }
}
