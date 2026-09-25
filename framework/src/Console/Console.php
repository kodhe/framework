<?php

declare(strict_types=1);

namespace Kodhe\Framework\Console;

use Kodhe\Framework\Console\Exceptions\CommandNotFoundException;
use RuntimeException;

/**
 * Console Application Manager
 */
class Console
{
    protected static ?Console $instance = null;
    
    protected array $commands = [];
    protected array $aliases = [];
    protected string $name = 'Kodhe Framework';
    protected string $version = '1.0.0';
    protected Output $output;
    protected Input $input;

    /**
     * Private constructor for singleton pattern
     */
    private function __construct()
    {
        $this->output = new Output();
        $this->input = new Input();
        $this->registerDefaultCommands();
    }

    /**
     * Prevent cloning
     */
    private function __clone()
    {
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Register default commands
     */
    protected function registerDefaultCommands(): void
    {
        // Register built-in commands
        $this->addCommand(new Commands\HelpCommand());
        $this->addCommand(new Commands\ListCommand($this));
        $this->addCommand(new Commands\VersionCommand($this->version));
        $this->addCommand(new Commands\MakeCommand());
        $this->addCommand(new Commands\NewProjectCommand());
        $this->addCommand(new Commands\ServeCommand());
    }

    /**
     * Add a command
     */
    public function addCommand(Command $command): self
    {
        $name = $command->getName();
        
        if (empty($name)) {
            throw new RuntimeException('Command name cannot be empty');
        }

        $this->commands[$name] = $command;

        // Register aliases
        foreach ($command->getAliases() as $alias) {
            $this->aliases[$alias] = $name;
        }

        // Register sub-command style aliases: a command named "make" that
        // declares usage entries like "make:controller <name>" also becomes
        // callable as "php console make:controller ...".
        if (!str_contains($name, ':')) {
            foreach ($command->getUsage() as $usage) {
                $first = explode(' ', trim($usage))[0] ?? '';
                if (str_starts_with($first, $name . ':') && !isset($this->aliases[$first])) {
                    $this->aliases[$first] = $name;
                }
            }
        }

        return $this;
    }

    /**
     * Add multiple commands
     */
    public function addCommands(array $commands): self
    {
        foreach ($commands as $command) {
            if ($command instanceof Command) {
                $this->addCommand($command);
            }
        }

        return $this;
    }

    /**
     * Check if a command exists
     */
    public function hasCommand(string $name): bool
    {
        return isset($this->commands[$name]) || isset($this->aliases[$name]);
    }

    /**
     * Get a command by name or alias
     */
    public function getCommand(string $name): ?Command
    {
        // Resolve alias if exists
        if (isset($this->aliases[$name])) {
            $name = $this->aliases[$name];
        }

        return $this->commands[$name] ?? null;
    }

    /**
     * Get all registered commands
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Get all registered aliases (alias => canonical command name)
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * Get the canonical command name for a name or alias
     */
    public function resolveCommandName(string $name): ?string
    {
        if (isset($this->commands[$name])) {
            return $name;
        }

        return $this->aliases[$name] ?? null;
    }

    /**
     * Run the console application
     */
    public function run(?Input $input = null, ?Output $output = null): int
    {
        $this->input = $input ?? new Input();
        $this->output = $output ?? new Output();

        // Map global verbosity flags to the output instance.
        $options = $this->input->getOptions();
        if (!empty($options['quiet']) || !empty($options['q'])) {
            $this->output->setVerbosity(Output::VERBOSITY_QUIET);
        } elseif (!empty($options['vvv'])) {
            $this->output->setVerbosity(Output::VERBOSITY_DEBUG);
        } elseif (!empty($options['vv'])) {
            $this->output->setVerbosity(Output::VERBOSITY_VERY_VERBOSE);
        } elseif (!empty($options['v'])) {
            $this->output->setVerbosity(Output::VERBOSITY_VERBOSE);
        }

        $commandName = $this->input->getFirstArgument();

        if ($commandName === null) {
            // No command specified, show help
            $commandName = 'help';
        }

        try {
            return $this->runCommand($commandName, $this->input->getArguments(), $this->output);
        } catch (CommandNotFoundException $e) {
            $this->output->error("Command '{$commandName}' not found.");
            $this->output->writeln('Run "console help" to see available commands.');
            return 1;
        } catch (\Throwable $e) {
            $this->output->error($e->getMessage());
            
            if ($this->output->isDebug()) {
                $this->output->writeln('');
                $this->output->writeln('<debug>Stack trace:</debug>');
                $this->output->writeln($e->getTraceAsString());
            }
            
            return 1;
        }
    }

    /**
     * Run a specific command
     */
    public function runCommand(string $commandName, array $arguments, Output $output): int
    {
        $command = $this->getCommand($commandName);

        if ($command === null) {
            throw new CommandNotFoundException("Command '{$commandName}' not found");
        }

        // Rebuild argv from the ORIGINAL tokens (the parsed positional
        // arguments alone would silently drop every --option/--flag), then
        // create a fresh Input so relative offsets are stable regardless of
        // whether an alias or the canonical name was typed on the CLI.
        $tokens = $this->input->getTokens();
        $cmdIndex = array_search($commandName, $tokens, true);
        if ($cmdIndex === false) {
            $cmdIndex = array_search($this->resolveCommandName($commandName) ?? '', $tokens, true);
        }
        $argv = $cmdIndex === false
            ? array_merge([$commandName], $arguments)
            : array_slice($tokens, $cmdIndex);

        $input = new Input(array_merge([$commandName], array_slice($argv, 1)));
        
        $command->setInput($input);
        $command->setOutput($output);

        return $command->run($input, $output);
    }

    /**
     * Set the console name
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the console name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the console version
     */
    public function setVersion(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    /**
     * Get the console version
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Get the output instance
     */
    public function getOutput(): Output
    {
        return $this->output;
    }

    /**
     * Get the input instance
     */
    public function getInput(): Input
    {
        return $this->input;
    }

    /**
     * Get longest command name length
     */
    public function getLongestCommandName(): int
    {
        $max = 0;
        foreach ($this->commands as $command) {
            $max = max($max, strlen($command->getName()));
        }
        return $max;
    }
}
