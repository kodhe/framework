<?php

declare(strict_types=1);

namespace Kodhe\Validation\Exceptions;

/**
 * Rule Not Found Exception
 */
class RuleNotFoundException extends ValidationException
{
    public function __construct(string $ruleName)
    {
        parent::__construct(sprintf('Validation rule "%s" not found', $ruleName));
    }
}
