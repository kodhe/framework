<?php

declare(strict_types=1);

namespace Kodhe\Framework\Console;

/**
 * Output Interface for Console Commands
 */
interface OutputInterface
{
    public const VERBOSITY_QUIET = 0;
    public const VERBOSITY_NORMAL = 1;
    public const VERBOSITY_VERBOSE = 2;
    public const VERBOSITY_VERY_VERBOSE = 3;
    public const VERBOSITY_DEBUG = 4;

    /**
     * Write a message without newline
     */
    public function write(string $message, bool $newline = false): void;

    /**
     * Write a message with newline
     */
    public function writeln(string $message): void;

    /**
     * Set verbosity level
     */
    public function setVerbosity(int $level): void;

    /**
     * Get verbosity level
     */
    public function getVerbosity(): int;

    /**
     * Check if output is quiet
     */
    public function isQuiet(): bool;

    /**
     * Check if output is verbose
     */
    public function isVerbose(): bool;

    /**
     * Check if output is very verbose
     */
    public function isVeryVerbose(): bool;

    /**
     * Check if output is debug mode
     */
    public function isDebug(): bool;

    /**
     * Write info message
     */
    public function info(string $message): void;

    /**
     * Write success message
     */
    public function success(string $message): void;

    /**
     * Write warning message
     */
    public function warning(string $message): void;

    /**
     * Write error message
     */
    public function error(string $message): void;

    /**
     * Write debug message (only in debug mode)
     */
    public function debug(string $message): void;

    /**
     * Format table output
     */
    public function table(array $headers, array $rows): void;

    /**
     * Ask a question and get user input
     */
    public function ask(string $question, mixed $default = null): mixed;

    /**
     * Ask for confirmation
     */
    public function confirm(string $question, bool $default = true): bool;

    /**
     * Choose from options
     */
    public function choice(string $question, array $options, mixed $default = null): mixed;

    /**
     * Show progress bar
     */
    public function progress(int $current, int $total, string $message = ''): void;
}
