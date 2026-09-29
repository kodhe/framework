<?php

declare(strict_types=1);

namespace Kodhe\Framework\Console\Commands;

use Kodhe\Framework\Console\Command;

/**
 * Serve Command - Start the PHP built-in development server.
 *
 * Equivalent of Laravel's `php artisan serve` / CI4's `spark serve`:
 * boots `php -S` with public/index.php as the router script so a freshly
 * scaffolded project (see `new`) can be run without configuring Apache/Nginx.
 */
class ServeCommand extends Command
{
    protected string $name = 'serve';
    protected string $description = 'Start the PHP built-in development server (public/)';
    protected array $usage = [
        'serve',
        'serve --host=0.0.0.0 --port=8080',
    ];
    protected array $arguments = [];
    protected array $options = [
        'host' => 'Host/interface to bind to (default: localhost)',
        'port' => 'TCP port to listen on (default: 8080)',
    ];
    protected array $aliases = ['run-server'];

    /**
     * {@inheritdoc}
     */
    public function handle(): int
    {
        if (PHP_SAPI !== 'cli') {
            $this->error('The serve command must be run from the command line.');
            return 1;
        }

        if (!function_exists('proc_open')) {
            $this->error('The serve command requires the proc_open() function (disabled in php.ini?).');
            return 1;
        }

        $host = (string) ($this->option('host') ?: 'localhost');
        $port = (int) ($this->option('port') ?: 8080);

        if ($port < 1 || $port > 65535) {
            $this->error("Invalid port '{$port}'. Use a number between 1 and 65535.");
            return 1;
        }

        [$root, $index] = $this->locateProject();

        if ($index === null) {
            $this->error('Could not find public/index.php. Run this command from your project root, or create one with: php console new <directory>');
            return 1;
        }

        $this->info('Kodhe Framework development server');
        $this->writeln(sprintf('  Document root : %s/public', $root));
        $this->writeln(sprintf('  Listening on  : http://%s:%d', $host, $port));
        $this->writeln('  Press Ctrl+C to stop the server.');
        $this->writeln('');

        $command = sprintf(
            '%s -S %s:%d -t %s %s',
            escapeshellarg(PHP_BINARY),
            escapeshellarg($host),
            $port,
            escapeshellarg($root . '/public'),
            escapeshellarg($index)
        );

        $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes);

        if (!is_resource($process)) {
            $this->error('Failed to start the PHP development server process.');
            return 1;
        }

        // Forward SIGINT/SIGTERM so Ctrl+C stops both processes cleanly.
        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            $stop = static function () use ($process): void {
                if (is_resource($process)) {
                    proc_terminate($process, SIGTERM);
                }
            };
            pcntl_signal(SIGINT, $stop);
            pcntl_signal(SIGTERM, $stop);
        }

        $exitCode = proc_close($process);

        $this->writeln('');
        $this->info('Development server stopped.');

        return $exitCode === 0 ? 0 : 1;
    }

    /**
     * Find the project root + front controller.
     *
     * Search order: current working directory, then walk up a few levels
     * (covers running `php ../framework/bin/console serve` from a project).
     *
     * @return array{0:string,1:?string} [root, index path or null]
     */
    protected function locateProject(): array
    {
        $dir = getcwd() ?: __DIR__;

        for ($i = 0; $i < 6; $i++) {
            foreach (["{$dir}/public/index.php", "{$dir}/Public/index.php"] as $candidate) {
                if (is_file($candidate)) {
                    return [$dir, $candidate];
                }
            }

            $parent = dirname($dir);
            if ($parent === $dir) {
                break;
            }
            $dir = $parent;
        }

        return [getcwd() ?: '', null];
    }
}
