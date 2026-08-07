<?php

declare(strict_types=1);

namespace Kodhe\Framework\Console\Commands;

/**
 * List Command - List all available commands
 */
class ListCommand extends Command
{
    protected string $name = 'list';
    protected string $description = 'List all available commands';
    protected array $usage = [
        'list',
        'list --raw',
    ];
    protected array $options = [
        'raw' => 'Output the command list as raw text',
    ];
    protected array $aliases = ['ls'];

    private Console $console;

    /**
     * Constructor
     */
    public function __construct(Console $console)
    {
        $this->console = $console;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(): int
    {
        if ($this->option('raw')) {
            return $this->listRaw();
        }

        return $this->listFormatted();
    }

    /**
     * List commands in raw format
     */
    protected function listRaw(): int
    {
        $commands = $this->console->getCommands();
        
        foreach ($commands as $command) {
            $this->writeln($command->getName());
        }

        return 0;
    }

    /**
     * List commands in formatted table
     */
    protected function listFormatted(): int
    {
        $commands = $this->console->getCommands();

        $this->writeln('');
        $this->writeln("<info>{$this->console->getName()}</info> version <comment>{$this->console->getVersion()}</comment>");
        $this->writeln('');

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
                $this->writeln("<comment>{$namespace}</comment>");
                $this->writeln(str_repeat('-', 40));
            }

            $rows = [];
            foreach ($cmds as $cmd) {
                $rows[] = [
                    "<info>{$cmd->getName()}</info>",
                    $cmd->getDescription(),
                ];
            }

            $this->table(['Command', 'Description'], $rows);
            
            if ($namespace !== '') {
                $this->writeln('');
            }
        }

        $this->writeln('');
        $this->writeln('Use <comment>help [command]</comment> for more information about a command.');

        return 0;
    }
}
