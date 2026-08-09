<?php

declare(strict_types=0);

namespace Kodhe\Framework\Typography\ValueObjects;

/**
 * Typographic Configuration Value Object
 */
class TypographicConfig
{
    /**
     * @var string Block level elements
     */
    public $blockElements = 'address|blockquote|div|dl|fieldset|form|h\d|hr|noscript|object|ol|p|pre|script|table|ul';

    /**
     * @var string Elements to skip
     */
    public $skipElements = 'p|pre|ol|ul|dl|object|table|h\d';

    /**
     * @var string Inline elements
     */
    public $inlineElements = 'a|abbr|acronym|b|bdo|big|br|button|cite|code|del|dfn|em|i|img|ins|input|label|map|kbd|q|samp|select|small|span|strong|sub|sup|textarea|tt|var';

    /**
     * @var array Inner block required elements
     */
    public $innerBlockRequired = ['blockquote'];

    /**
     * @var bool Whether to protect quotes in braces
     */
    public $protectBracedQuotes = false;

    /**
     * Create config from array.
     *
     * @param array $config
     * @return self
     */
    public static function fromArray(array $config): self
    {
        $instance = new self();

        foreach ($config as $key => $value) {
            $property = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
            if (property_exists($instance, $property)) {
                $instance->$property = $value;
            }
        }

        return $instance;
    }

    /**
     * Convert to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'block_elements' => $this->blockElements,
            'skip_elements' => $this->skipElements,
            'inline_elements' => $this->inlineElements,
            'inner_block_required' => $this->innerBlockRequired,
            'protect_braced_quotes' => $this->protectBracedQuotes,
        ];
    }
}
