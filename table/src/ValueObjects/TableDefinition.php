<?php

declare(strict_types=0);

namespace Kodhe\Framework\Table\ValueObjects;

/**
 * Value object for table definition
 */
class TableDefinition
{
    /**
     * @var array The heading data
     */
    private array $heading = [];

    /**
     * @var array The rows data
     */
    private array $rows = [];

    /**
     * @var string|null The caption
     */
    private ?string $caption = null;

    /**
     * @var bool Auto heading flag
     */
    private bool $autoHeading = true;

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        if (isset($config['heading'])) {
            $this->heading = $config['heading'];
        }
        if (isset($config['rows'])) {
            $this->rows = $config['rows'];
        }
        if (isset($config['caption'])) {
            $this->caption = $config['caption'];
        }
        if (isset($config['auto_heading'])) {
            $this->autoHeading = $config['auto_heading'];
        }
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
     * Set the heading
     *
     * @param array $heading
     * @return self
     */
    public function setHeading(array $heading): self
    {
        $this->heading = $heading;
        return $this;
    }

    /**
     * Get the rows
     *
     * @return array
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    /**
     * Set the rows
     *
     * @param array $rows
     * @return self
     */
    public function setRows(array $rows): self
    {
        $this->rows = $rows;
        return $this;
    }

    /**
     * Add a row
     *
     * @param array $row
     * @return self
     */
    public function addRow(array $row): self
    {
        $this->rows[] = $row;
        return $this;
    }

    /**
     * Get the caption
     *
     * @return string|null
     */
    public function getCaption(): ?string
    {
        return $this->caption;
    }

    /**
     * Set the caption
     *
     * @param string $caption
     * @return self
     */
    public function setCaption(string $caption): self
    {
        $this->caption = $caption;
        return $this;
    }

    /**
     * Check if auto heading is enabled
     *
     * @return bool
     */
    public function isAutoHeading(): bool
    {
        return $this->autoHeading;
    }

    /**
     * Set auto heading
     *
     * @param bool $autoHeading
     * @return self
     */
    public function setAutoHeading(bool $autoHeading): self
    {
        $this->autoHeading = $autoHeading;
        return $this;
    }

    /**
     * Check if table has data
     *
     * @return bool
     */
    public function hasData(): bool
    {
        return !empty($this->heading) || !empty($this->rows);
    }

    /**
     * Clear all data
     *
     * @return self
     */
    public function clear(): self
    {
        $this->heading = [];
        $this->rows = [];
        $this->caption = null;
        $this->autoHeading = true;
        return $this;
    }
}
