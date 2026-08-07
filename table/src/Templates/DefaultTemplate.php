<?php

declare(strict_types=1);

namespace Kodhe\Table\Templates;

use Kodhe\Table\Contracts\TemplateInterface;
use Kodhe\Table\Support\TemplateResolver;

/**
 * Default template implementation
 */
class DefaultTemplate implements TemplateInterface
{
    /**
     * @var array The template data
     */
    private array $template = [];

    /**
     * @var TemplateResolver The template resolver
     */
    private TemplateResolver $resolver;

    /**
     * Constructor
     *
     * @param array|null $template
     * @param TemplateResolver|null $resolver
     */
    public function __construct(?array $template = null, ?TemplateResolver $resolver = null)
    {
        $this->resolver = $resolver ?? new TemplateResolver();
        
        if ($template !== null) {
            $this->template = $this->resolver->resolve($template);
        } else {
            $this->template = $this->resolver->getDefaultTemplate();
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
        $this->template = $this->resolver->merge($template);
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
     * Set a specific template value
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function set(string $key, $value): self
    {
        $this->template[$key] = $value;
        return $this;
    }

    /**
     * Reset to default template
     *
     * @return self
     */
    public function reset(): self
    {
        $this->template = $this->resolver->getDefaultTemplate();
        return $this;
    }
}
