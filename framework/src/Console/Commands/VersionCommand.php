<?php

declare(strict_types=1);

namespace Kodhe\Framework\Console\Commands;

/**
 * Version Command - Display the framework version
 */
class VersionCommand extends Command
{
    protected string $name = 'version';
    protected string $description = 'Display the framework version';
    protected array $usage = [
        'version',
        'version --short',
    ];
    protected array $options = [
        'short' => 'Output only the version number',
    ];
    protected array $aliases = ['-v', '--version'];

    private string $version;

    /**
     * Constructor
     */
    public function __construct(string $version)
    {
        $this->version = $version;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(): int
    {
        if ($this->option('short')) {
            $this->writeln($this->version);
            return 0;
        }

        $console = Console::getInstance();
        
        $this->writeln('');
        $this->writeln("<info>{$console->getName()}</info>");
        $this->writeln("Version: <comment>{$this->version}</comment>");
        $this->writeln('');
        $this->writeln('PHP Version: ' . PHP_VERSION);
        $this->writeln('OS: ' . PHP_OS);
        $this->writeln('');

        return 0;
    }
}
