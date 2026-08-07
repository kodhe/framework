<?php

declare(strict_types=1);

namespace Kodhe\Table\Templates;

use Kodhe\Table\Contracts\TemplateInterface;

/**
 * Template adapter for backward compatibility
 */
class TemplateAdapter implements TemplateInterface
{
    /**
     * @var array The template data (legacy format)
     */
    private array $template = [];

    /**
     * Constructor
     *
     * @param array|null $template
     */
    public function __construct(?array $template = null)
    {
        if ($template !== null) {
            $this->template = $template;
        }
    }

    /**
     * Get the template array
     *
     * @return array
     */
    public function getTemplate(): array
    {
        return $this->template;
    }

    /**
     * Set or merge template values
     *
     * @param array $template
     * @return self
     */
    public function setTemplate(array $template): self
    {
        $this->template = $template;
        return $this;
    }

    /**
     * Get a specific template value
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        return $this->template[$key] ?? null;
    }

    /**
     * Check if template is set
     *
     * @return bool
     */
    public function isSet(): bool
    {
        return !empty($this->template);
    }

    /**
     * Clear template
     *
     * @return self
     */
    public function clear(): self
    {
        $this->template = [];
        return $this;
    }
}
