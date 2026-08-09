<?php

declare(strict_types=1);

namespace Kodhe\Framework\Console;

/**
 * Console Output Implementation
 */
class Output implements OutputInterface
{
    protected int $verbosity = self::VERBOSITY_NORMAL;
    
    /** @var resource */
    protected $stream;

    /**
     * Constructor
     * 
     * @param resource|null $stream Output stream (defaults to STDOUT)
     */
    public function __construct($stream = null)
    {
        $this->stream = $stream ?? fopen('php://stdout', 'w');
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $message, bool $newline = false): void
    {
        if ($newline) {
            $message .= PHP_EOL;
        }
        
        fwrite($this->stream, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function writeln(string $message): void
    {
        $this->write($message . PHP_EOL);
    }

    /**
     * {@inheritdoc}
     */
    public function setVerbosity(int $level): void
    {
        $this->verbosity = max(self::VERBOSITY_QUIET, min(self::VERBOSITY_DEBUG, $level));
    }

    /**
     * {@inheritdoc}
     */
    public function getVerbosity(): int
    {
        return $this->verbosity;
    }

    /**
     * {@inheritdoc}
     */
    public function isQuiet(): bool
    {
        return $this->verbosity === self::VERBOSITY_QUIET;
    }

    /**
     * {@inheritdoc}
     */
    public function isVerbose(): bool
    {
        return $this->verbosity >= self::VERBOSITY_VERBOSE;
    }

    /**
     * {@inheritdoc}
     */
    public function isVeryVerbose(): bool
    {
        return $this->verbosity >= self::VERBOSITY_VERY_VERBOSE;
    }

    /**
     * {@inheritdoc}
     */
    public function isDebug(): bool
    {
        return $this->verbosity >= self::VERBOSITY_DEBUG;
    }

    /**
     * {@inheritdoc}
     */
    public function info(string $message): void
    {
        $this->writeln("<info>$message</info>");
    }

    /**
     * {@inheritdoc}
     */
    public function success(string $message): void
    {
        $this->writeln("<success>$message</success>");
    }

    /**
     * {@inheritdoc}
     */
    public function warning(string $message): void
    {
        $this->writeln("<warning>$message</warning>");
    }

    /**
     * {@inheritdoc}
     */
    public function error(string $message): void
    {
        $this->writeln("<error>$message</error>");
    }

    /**
     * {@inheritdoc}
     */
    public function debug(string $message): void
    {
        if ($this->isDebug()) {
            $this->writeln("<debug>$message</debug>");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function table(array $headers, array $rows): void
    {
        if (empty($headers) && empty($rows)) {
            return;
        }

        // Calculate column widths
        $widths = [];
        foreach ($headers as $i => $header) {
            $widths[$i] = strlen((string) $header);
        }

        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                if (!isset($widths[$i])) {
                    $widths[$i] = 0;
                }
                $widths[$i] = max($widths[$i], strlen((string) $cell));
            }
        }

        // Create border
        $border = '+';
        foreach ($widths as $width) {
            $border .= str_repeat('-', $width + 2) . '+';
        }

        // Output header
        $this->writeln($border);
        $this->writeln('| ' . implode(' | ', array_map(fn($h, $i) => str_pad((string) $h, $widths[$i]), $headers, array_keys($headers))) . ' |');
        $this->writeln($border);

        // Output rows
        foreach ($rows as $row) {
            $cells = [];
            foreach ($row as $i => $cell) {
                $cells[] = str_pad((string) $cell, $widths[$i] ?? 0);
            }
            $this->writeln('| ' . implode(' | ', $cells) . ' |');
        }

        $this->writeln($border);
    }

    /**
     * {@inheritdoc}
     */
    public function ask(string $question, mixed $default = null): mixed
    {
        $this->write($question . ($default !== null ? " [$default]" : '') . ': ');
        
        $input = fgets(STDIN);
        $result = trim($input ?? '');
        
        return $result !== '' ? $result : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function confirm(string $question, bool $default = true): bool
    {
        $choice = $this->ask($question . ' (Y/n)', $default ? 'Y' : 'n');
        $choice = strtoupper($choice ?? '');
        
        return in_array($choice, ['Y', 'YES'], true);
    }

    /**
     * {@inheritdoc}
     */
    public function choice(string $question, array $options, mixed $default = null): mixed
    {
        $this->writeln($question);
        
        foreach ($options as $key => $option) {
            $this->writeln("  [$key] $option");
        }
        
        $defaultKey = $default !== null ? array_search($default, $options, true) : null;
        $choice = $this->ask('Choice', $defaultKey);
        
        return $options[$choice] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function progress(int $current, int $total, string $message = ''): void
    {
        if ($total === 0) {
            return;
        }

        $percent = ($current / $total) * 100;
        $barLength = 50;
        $filled = (int) ($barLength * $current / $total);
        $empty = $barLength - $filled;

        $bar = str_repeat('=', $filled) . str_repeat('-', $empty);
        
        $output = sprintf("\r[%s] %3d%% %s", $bar, $percent, $message);
        $this->write($output);

        if ($current >= $total) {
            $this->writeln('');
        }
    }

    /**
     * Format text with ANSI colors
     */
    public function format(string $message, string $style = ''): string
    {
        $styles = [
            'info' => "\033[36m%s\033[0m",
            'success' => "\033[32m%s\033[0m",
            'warning' => "\033[33m%s\033[0m",
            'error' => "\033[31m%s\033[0m",
            'debug' => "\033[37m%s\033[0m",
        ];

        if (isset($styles[$style])) {
            return sprintf($styles[$style], $message);
        }

        return $message;
    }

    /**
     * Clear line
     */
    public function clearLine(): void
    {
        $this->write("\r\033[K");
    }

    /**
     * Move cursor up
     */
    public function moveCursorUp(int $lines = 1): void
    {
        $this->write("\033[{$lines}A");
    }

    /**
     * Get the output stream
     * 
     * @return resource
     */
    public function getStream()
    {
        return $this->stream;
    }
}
