<?php

declare(strict_types=1);

namespace Kodhe\Framework\Console\Commands;

/**
 * Help Command - Display help information for commands
 */
class HelpCommand extends Command
{
    protected string $name = 'help';
    protected string $description = 'Display help information for a command or list all commands';
    protected array $usage = [
        'help',
        'help <command_name>',
    ];
    protected array $arguments = [
        'command_name' => 'The name of the command to get help for (optional)',
    ];
    protected array $options = [];
    protected array $aliases = ['?'];

    /**
     * {@inheritdoc}
     */
    public function handle(): int
    {
        $commandName = $this->argument('command_name');

        if ($commandName) {
            // Show help for specific command
            return $this->showCommandHelp($commandName);
        }

        // Show general help
        return $this->showGeneralHelp();
    }

    /**
     * Show help for a specific command
     */
    protected function showCommandHelp(string $name): int
    {
        $console = Console::getInstance();
        $command = $console->getCommand($name);

        if ($command === null) {
            $this->error("Command '{$name}' not found.");
            return 1;
        }

        $this->writeln('');
        $this->writeln("<info>Usage:</info>");
        $this->writeln("  {$command->getName()}");

        $usage = $command->getUsage();
        if (!empty($usage)) {
            $this->writeln('');
            $this->writeln('<info>Examples:</info>');
            foreach ($usage as $example) {
                $this->writeln("  $example");
            }
        }

        $this->writeln('');
        $this->writeln("<info>Description:</info>");
        $this->writeln("  {$command->getDescription()}");

        $arguments = $command->getArguments();
        if (!empty($arguments)) {
            $this->writeln('');
            $this->writeln('<info>Arguments:</info>');
            foreach ($arguments as $arg => $desc) {
                $this->writeln("  <comment>{$arg}</comment>: {$desc}");
            }
        }

        $options = $command->getOptions();
        if (!empty($options)) {
            $this->writeln('');
            $this->writeln('<info>Options:</info>');
            foreach ($options as $opt => $desc) {
                $this->writeln("  <comment>--{$opt}</comment>: {$desc}");
            }
        }

        $aliases = $command->getAliases();
        if (!empty($aliases)) {
            $this->writeln('');
            $this->writeln('<info>Aliases:</info>');
            $this->writeln('  ' . implode(', ', $aliases));
        }

        $this->writeln('');
        return 0;
    }

    /**
     * Show general help with all available commands
     */
    protected function showGeneralHelp(): int
    {
        $console = Console::getInstance();
        $commands = $console->getCommands();

        $this->writeln('');
        $this->writeln("<info>{$console->getName()}</info> version <comment>{$console->getVersion()}</comment>");
        $this->writeln('');
        $this->writeln('<info>Available Commands:</info>');

        // Group commands by namespace
        $grouped = [];
        foreach ($commands as $command) {
            $parts = explode(':', $command->getName());
            $namespace = $parts[0] ?? '';
            
            if (!isset($grouped[$namespace])) {
                $grouped[$namespace] = [];
            }
            
            $grouped[$namespace][] = $command;
        }

        foreach ($grouped as $namespace => $cmds) {
            if ($namespace !== '') {
                $this->writeln('');
                $this->writeln("<comment>{$namespace}</comment>");
            }

            foreach ($cmds as $cmd) {
                $name = str_pad($cmd->getName(), 20);
                $this->writeln("  <info>{$name}</info> {$cmd->getDescription()}");
            }
        }

        $this->writeln('');
        $this->writeln('<info>Usage:</info>');
        $this->writeln('  console <command> [options] [arguments]');
        $this->writeln('');
        $this->writeln('Use <comment>console help <command></comment> to see detailed help for a specific command.');

        return 0;
    }
}
