<?php

declare(strict_types=1);

namespace Kodhe\Validation\Filters;

use Kodhe\Validation\Contracts\FilterInterface;

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
