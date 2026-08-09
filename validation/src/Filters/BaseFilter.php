<?php

declare(strict_types=1);

namespace Kodhe\Framework\Validation\Filters;

use Kodhe\Framework\Validation\Contracts\FilterInterface;

/**
 * Base Filter Class
 */
abstract class BaseFilter implements FilterInterface
{
    /**
     * Filter/sanitize a value
     */
    abstract public function filter(mixed $value): mixed;
}
