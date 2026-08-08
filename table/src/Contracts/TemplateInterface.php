<?php

declare(strict_types=0);

namespace Kodhe\Framework\Table\Contracts;

/**
 * Interface for table templates
 */
interface TemplateInterface
{
    /**
     * Get the template array
     *
     * @return array
     */
    public function getTemplate(): array;

    /**
     * Set or merge template values
     *
     * @param array $template
     * @return self
     */
    public function setTemplate(array $template): self;

    /**
     * Get a specific template value
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key);
}
