<?php

declare(strict_types=0);

namespace Kodhe\Framework\Console;

/**
 * Console Input Implementation
 */
class Input implements InputInterface
{
    protected array $tokens = [];
    protected array $arguments = [];
    protected array $options = [];
    protected string $commandName = '';

    /**
     * Constructor
     * 
     * @param array|null $tokens Command line tokens (defaults to $_SERVER['argv'])
     */
    public function __construct(?array $tokens = null)
    {
        if ($tokens === null) {
            $tokens = $_SERVER['argv'] ?? [];
        }

        $this->tokens = $tokens;
        $this->parse();
    }

    /**
     * Parse command line tokens
     */
    protected function parse(): void
    {
        if (empty($this->tokens)) {
            return;
        }

        // First token is usually the script name, second is command name
        $this->tokens = array_values($this->tokens);
        
        // Skip script name if present
        $startIndex = 0;
        if (isset($this->tokens[0]) && str_ends_with($this->tokens[0], 'console')) {
            $startIndex = 1;
        }

        // Get command name
        if (isset($this->tokens[$startIndex])) {
            $this->commandName = $this->tokens[$startIndex];
            $this->arguments['command'] = $this->commandName;
        }

        $i = $startIndex + 1;
        $count = count($this->tokens);

        while ($i < $count) {
            $token = $this->tokens[$i];

            if ($token === '--') {
                // End of options
                break;
            }

            if (str_starts_with($token, '--')) {
                // Long option
                $this->parseLongOption($token, $i, $count);
                $i++;
            } elseif (str_starts_with($token, '-') && strlen($token) > 1) {
                // Short option(s)
                $this->parseShortOptions($token, $i, $count);
                $i++;
            } else {
                // Argument
                $this->arguments[] = $token;
                $i++;
            }
        }
    }

    /**
     * Parse long option (--option=value or --option)
     */
    protected function parseLongOption(string $token, int &$index, int $count): void
    {
        $parts = explode('=', $token, 2);
        $name = ltrim($parts[0], '-');

        if (isset($parts[1])) {
            // --option=value
            $this->options[$name] = $parts[1];
        } elseif (isset($this->tokens[$index + 1]) && !str_starts_with($this->tokens[$index + 1], '-')) {
            // --option value
            $this->options[$name] = $this->tokens[$index + 1];
            $index++;
        } else {
            // --option (boolean flag)
            $this->options[$name] = true;
        }
    }

    /**
     * Parse short options (-abc or -a value)
     */
    protected function parseShortOptions(string $token, int &$index, int $count): void
    {
        $chars = str_split(ltrim($token, '-'));
        
        foreach ($chars as $i => $char) {
            if ($i < count($chars) - 1) {
                // Multiple flags combined: -abc
                $this->options[$char] = true;
            } else {
                // Last char might have a value
                if (isset($this->tokens[$index + 1]) && !str_starts_with($this->tokens[$index + 1], '-')) {
                    $this->options[$char] = $this->tokens[$index + 1];
                    $index++;
                } else {
                    $this->options[$char] = true;
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function getArgument(string|int $name, mixed $default = null): mixed
    {
        if (is_int($name)) {
            $args = array_values($this->arguments);
            return $args[$name] ?? $default;
        }

        return $this->arguments[$name] ?? $default;
    }

    /**
     * {@inheritdoc}
     */
    public function hasArgument(string|int $name): bool
    {
        if (is_int($name)) {
            $args = array_values($this->arguments);
            return isset($args[$name]);
        }

        return isset($this->arguments[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function setArgument(string|int $name, mixed $value): void
    {
        if (is_int($name)) {
            $args = array_values($this->arguments);
            $args[$name] = $value;
            $this->arguments = $args;
        } else {
            $this->arguments[$name] = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function getOption(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }

    /**
     * {@inheritdoc}
     */
    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function setOption(string $name, mixed $value): void
    {
        $this->options[$name] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstArgument(): ?string
    {
        return $this->commandName ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function getTokens(): array
    {
        return $this->tokens;
    }

    /**
     * Get command name
     */
    public function getCommandName(): ?string
    {
        return $this->commandName ?: null;
    }
}
