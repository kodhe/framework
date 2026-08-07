<?php

namespace Kodhe\Framework\View\Variant;

/**
 * Class VariantResolver
 *
 * @package Kodhe\Framework\View\Variant
 */
class VariantResolver
{
    /**
     * @var Variant[]
     */
    protected $variants = [];

    /**
     * Default variant mappings
     *
     * @var array
     */
    protected $defaults = [
        'mobile' => [
            '/iPhone/i',
            '/Android.*Mobile/i',
            '/Windows Phone/i',
            '/BlackBerry/i',
            '/webOS/i',
            '/Opera Mini/i',
        ],
        'tablet' => [
            '/iPad/i',
            '/Android(?!.*Mobile)/i',
            '/Tablet/i',
            '/Touch/i',
        ],
        'desktop' => [
            '/Windows NT/i',
            '/Macintosh/i',
            '/Linux/i',
            '/X11/i',
        ],
    ];

    /**
     * Create a new VariantResolver instance
     *
     * @param array $variants
     */
    public function __construct(array $variants = [])
    {
        foreach ($variants ?: $this->defaults as $name => $patterns) {
            $this->variants[$name] = new Variant($name, $patterns);
        }
    }

    /**
     * Resolve variant from user agent
     *
     * @param string $userAgent
     * @return string
     */
    public function resolve(string $userAgent): string
    {
        foreach ($this->variants as $variant) {
            if ($variant->matches($userAgent)) {
                return $variant->getName();
            }
        }

        return 'desktop';
    }

    /**
     * Add a variant
     *
     * @param Variant $variant
     * @return self
     */
    public function addVariant(Variant $variant): self
    {
        $this->variants[$variant->getName()] = $variant;
        return $this;
    }

    /**
     * Get all variants
     *
     * @return Variant[]
     */
    public function getVariants(): array
    {
        return $this->variants;
    }
}
