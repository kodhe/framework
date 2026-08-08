<?php

declare(strict_types=0);

namespace Kodhe\Framework\Console\Exceptions;

use RuntimeException;

/**
 * Exception thrown when a command is not found
 */
class CommandNotFoundException extends RuntimeException
{
}
