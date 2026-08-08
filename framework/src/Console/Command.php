<?php

declare(strict_types=0);

namespace Kodhe\Framework\Console;

/**
 * Base Command Class
 */
abstract class Command
{
    protected ?Input $input = null;
    protected ?Output $output = null;

    /**
     * Command name
     */
    protected string $name = '';

    /**
     * Command description
     */
    protected string $description = '';

    /**
     * Command usage examples
     */
    protected array $usage = [];

    /**
     * Command arguments definition
     */
    protected array $arguments = [];

    /**
     * Command options definition
     */
    protected array $options = [];

    /**
     * Command aliases
     */
    protected array $aliases = [];

    /**
     * Set the input instance
     */
    public function setInput(Input $input): void
    {
        $this->input = $input;
    }

    /**
     * Set the output instance
     */
    public function setOutput(Output $output): void
    {
        $this->output = $output;
    }

    /**
     * Get the command name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the command description
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get command usage examples
     */
    public function getUsage(): array
    {
        return $this->usage;
    }

    /**
     * Get command arguments definition
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Get command options definition
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get command aliases
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * Execute the command
     * 
     * @return int Exit code (0 for success)
     */
    abstract public function handle(): int;

    /**
     * Run the command with given input and output
     */
    public function run(Input $input, Output $output): int
    {
        $this->input = $input;
        $this->output = $output;

        try {
            return $this->handle();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            
            if ($output->isDebug()) {
                $this->output->writeln('');
                $this->output->writeln('<debug>Stack trace:</debug>');
                $this->output->writeln($e->getTraceAsString());
            }
            
            return 1;
        }
    }

    /**
     * Get an argument value
     */
    protected function argument(string|int $name, mixed $default = null): mixed
    {
        return $this->input?->getArgument($name, $default);
    }

    /**
     * Get an option value
     */
    protected function option(string $name, mixed $default = null): mixed
    {
        return $this->input?->getOption($name, $default);
    }

    /**
     * Check if an argument exists
     */
    protected function hasArgument(string|int $name): bool
    {
        return $this->input?->hasArgument($name) ?? false;
    }

    /**
     * Check if an option exists
     */
    protected function hasOption(string $name): bool
    {
        return $this->input?->hasOption($name) ?? false;
    }

    /**
     * Write to output
     */
    protected function write(string $message, bool $newline = false): void
    {
        $this->output?->write($message, $newline);
    }

    /**
     * Write line to output
     */
    protected function writeln(string $message): void
    {
        $this->output?->writeln($message);
    }

    /**
     * Write info message
     */
    protected function info(string $message): void
    {
        $this->output?->info($message);
    }

    /**
     * Write success message
     */
    protected function success(string $message): void
    {
        $this->output?->success($message);
    }

    /**
     * Write warning message
     */
    protected function warning(string $message): void
    {
        $this->output?->warning($message);
    }

    /**
     * Write error message
     */
    protected function error(string $message): void
    {
        $this->output?->error($message);
    }

    /**
     * Write debug message
     */
    protected function debug(string $message): void
    {
        $this->output?->debug($message);
    }

    /**
     * Display a table
     */
    protected function table(array $headers, array $rows): void
    {
        $this->output?->table($headers, $rows);
    }

    /**
     * Ask a question
     */
    protected function ask(string $question, mixed $default = null): mixed
    {
        return $this->output?->ask($question, $default);
    }

    /**
     * Ask for confirmation
     */
    protected function confirm(string $question, bool $default = true): bool
    {
        return $this->output?->confirm($question, $default);
    }

    /**
     * Choose from options
     */
    protected function choice(string $question, array $options, mixed $default = null): mixed
    {
        return $this->output?->choice($question, $options, $default);
    }

    /**
     * Show progress
     */
    protected function progress(int $current, int $total, string $message = ''): void
    {
        $this->output?->progress($current, $total, $message);
    }

    /**
     * Call another command
     */
    protected function call(string $commandName, array $arguments = []): int
    {
        if (!$this->output instanceof Output) {
            throw new \RuntimeException('Output not set');
        }

        $console = Console::getInstance();
        return $console->runCommand($commandName, $arguments, $this->output);
    }
}
