<?php

declare(strict_types=1);

namespace Kodhe\Framework\Console\Commands;

use Kodhe\Framework\Console\Command;
use RuntimeException;

/**
 * New Project Command - Scaffold a fresh Kodhe Framework application.
 *
 * Creates the standard Kodhe folder layout (PascalCase, no underscores),
 * composer.json with PSR-4 "App\\" autoloading, bootstrap files, config,
 * .env template, and starter route/controller/model/view/middleware files
 * so `php console serve` works out of the box.
 */
class NewProjectCommand extends Command
{
    protected string $name = 'new';
    protected string $description = 'Create a new Kodhe Framework project skeleton';
    protected array $usage = [
        'new <directory>',
        'new <directory> --min',
        'new my-app && cd my-app && composer install && php console serve',
    ];
    protected array $arguments = [
        'directory' => 'Target directory for the new project (created if missing)',
    ];
    protected array $options = [
        'force' => 'Scaffold into a non-empty existing directory',
        'min'   => 'Minimal skeleton: folders + config only, no demo code',
    ];
    protected array $aliases = ['new:project', 'create-project'];

    /** @var string Absolute path of the project root being generated */
    private string $root = '';

    /** @var int Number of files written */
    private int $written = 0;

    /**
     * {@inheritdoc}
     */
    public function handle(): int
    {
        $dir = $this->argument(0) ?: $this->argument('directory');

        // When dispatched via an alias (e.g. "create-project my-app"),
        // argument 0 is the literal command name.
        if (is_string($dir) && !str_contains($dir, '/') && !preg_match('/^[A-Za-z]:/', $dir)) {
            if (in_array($dir, ['new', 'new:project', 'create-project'], true)) {
                $dir = $this->argument(1) ?: $this->argument(2);
            }
        }

        if (!$dir) {
            $this->error('Missing target directory. Usage: php console new <directory>');
            return 1;
        }

        $this->root = rtrim((string) realpath(dirname((string) $dir)) ?: getcwd(), '/') . '/' . basename((string) $dir);

        $parent = dirname($this->root);
        if (!is_dir($parent)) {
            $this->error("Parent directory does not exist: {$parent}");
            return 1;
        }

        if (is_dir($this->root) && scandir($this->root) !== false && $this->directoryIsNotEmpty($this->root) && !$this->option('force')) {
            $this->error("Directory '{$this->root}' already exists and is not empty. Use --force to scaffold anyway.");
            return 1;
        }

        if (!is_dir($this->root) && !mkdir($this->root, 0755, true) && !is_dir($this->root)) {
            $this->error("Failed to create directory: {$this->root}");
            return 1;
        }

        $minimal = (bool) $this->option('min');
        $appName = basename($this->root);

        $this->info("Creating new Kodhe Framework project in {$this->root}");
        $this->writeln('');

        // 1. Directory structure (Kodhe convention: PascalCase, no underscores).
        foreach ($this->directories() as $d) {
            if (!is_dir("{$this->root}/{$d}") && !mkdir("{$this->root}/{$d}", 0755, true)) {
                throw new RuntimeException("Failed to create {$d}");
            }
        }
        $this->writeDirectories();

        // 2. Root files.
        $this->putFile('composer.json', $this->composerStub($appName));
        $this->putFile('.env', $this->envStub());
        $this->putFile('.env.example', $this->envStub());
        $this->putFile('.gitignore', $this->gitignoreStub());
        $this->putFile('console', $this->consoleStub());
        $this->putFile('public/index.php', $this->indexStub());
        $this->putFile('app/Config/config.php', $this->configStub());
        $this->putFile('app/Config/routes.php', $this->routesStub());
        $this->putFile('app/Config/routes_modern.php', $this->modernRoutesStub());
        $this->putFile('readme.md', $this->readmeStub($appName));

        // 3. Demo / starter code unless --min.
        if (!$minimal) {
            $this->putFile('app/Controllers/Home.php', $this->homeControllerStub());
            $this->putFile('app/Models/Base.php', $this->baseModelStub());
            $this->putFile('app/Middleware/.gitkeep', '');
            $this->putFile('app/Views/home/index.php', $this->welcomeViewStub());
            $this->putFile('database/migrations/.gitkeep', '');
        } else {
            $this->putFile('app/Controllers/.gitkeep', '');
            $this->putFile('app/Models/.gitkeep', '');
            $this->putFile('app/Views/.gitkeep', '');
            $this->putFile('database/migrations/.gitkeep', '');
        }

        // Make the console entry point executable when possible.
        @chmod("{$this->root}/console", 0755);

        $this->writeln('');
        $this->success("Project '{$appName}' created ({$this->written} files).");
        $this->writeln('');
        $this->writeln('Next steps:');
        $this->writeln(sprintf('  cd %s', basename($this->root)));
        $this->writeln('  composer require kodhe/framework');
        $this->writeln('  php console serve        # start dev server on http://localhost:8080');
        $this->writeln('');
        $this->writeln('Then generate code with:');
        $this->writeln('  php console make:controller Blog');
        $this->writeln('  php console make:model Post');

        return 0;
    }

    /**
     * Standard Kodhe application directories.
     *
     * @return list<string>
     */
    protected function directories(): array
    {
        return [
            'app',
            'app/Controllers',
            'app/Models',
            'app/Middleware',
            'app/Config',
            'app/Views',
            'app/Libraries',
            'bin',
            'public',
            'storage',
            'storage/Cache',
            'storage/Logs',
            'storage/Session',
            'storage/Uploads',
            'database',
            'database/migrations',
            'tests',
        ];
    }

    /**
     * Create .gitkeep placeholders so empty dirs survive git.
     */
    protected function writeDirectories(): void
    {
        foreach (['storage/Cache', 'storage/Logs', 'storage/Session', 'storage/Uploads', 'app/Libraries', 'tests'] as $d) {
            $file = "{$this->root}/{$d}/.gitkeep";
            if (!is_file($file)) {
                file_put_contents($file, '');
            }
        }
    }

    /**
     * Write a file relative to the project root (overwrites silently).
     */
    protected function putFile(string $relative, string $contents): void
    {
        $path = "{$this->root}/{$relative}";
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($path, $contents);
        $this->written++;
        $this->debug("  wrote {$relative}");
    }

    protected function directoryIsNotEmpty(string $path): bool
    {
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                return true;
            }
        }
        return false;
    }

    // ------------------------------------------------------------------
    // Stubs
    // ------------------------------------------------------------------

    protected function composerStub(string $appName): string
    {
        $name = preg_replace('/[^a-z0-9._-]+/', '-', strtolower($appName)) ?? 'app';

        return <<<JSON
{
    "name": "app/{$name}",
    "description": "Kodhe Framework application",
    "type": "project",
    "license": "MIT",
    "require": {
        "php": ">=8.1",
        "kodhe/framework": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "App\\\\": "app/"
        }
    },
    "scripts": {
        "serve": "php console serve"
    },
    "config": {
        "optimize-autoloader": true
    }
}

JSON;
    }

    protected function envStub(): string
    {
        return <<<'ENV'
APP_NAME=Kodhe App
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8080

# Database (used by kodhe/database / CI-compat config)
DB_HOST=127.0.0.1
DB_USERNAME=root
DB_PASSWORD=
DB_DATABASE=kodhe_app
DB_DRIVER=mysql

# Session — file driver requires a writable path
SESS_SAVE_PATH=storage/Session
ENV;
    }

    protected function gitignoreStub(): string
    {
        return <<<'GIT'
/vendor/
/storage/Cache/*
/storage/Logs/*
/storage/Session/*
!/storage/*/.gitkeep
.env
GIT;
    }

    protected function consoleStub(): string
    {
        return <<<'PHP'
#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Kodhe Framework Console — project entry point.
 */
foreach ([__DIR__ . '/vendor/autoload.php', __DIR__ . '/../vendor/autoload.php'] as $autoload) {
    if (is_file($autoload)) {
        require_once $autoload;
        break;
    }
}

if (!class_exists(Kodhe\Framework\Console\Console::class)) {
    fwrite(STDERR, "Error: kodhe/framework not installed. Run 'composer require kodhe/framework'.\n");
    exit(1);
}

use Kodhe\Framework\Console\Console;

$console = Console::getInstance();
exit($console->run());

PHP;
    }

    protected function indexStub(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

/**
 * Front controller — Kodhe Framework.
 *
 * Point your web server document root at this directory,
 * or run `php console serve` for development.
 */
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Load Composer autoloader (project root or parent install).
foreach ([__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../../vendor/autoload.php'] as $autoload) {
    if (is_file($autoload)) {
        require_once $autoload;
        break;
    }
}

if (!class_exists(Kodhe\Framework\Application::class)) {
    http_response_code(500);
    exit('kodhe/framework is not installed. Run: composer require kodhe/framework');
}

$app = Kodhe\Framework\Application::create(__DIR__ . '/..');
$app->bootstrap()->run();

PHP;
    }

    protected function configStub(): string
    {
        return <<<'PHP'
<?php

defined('ENVIRONMENT') || define('ENVIRONMENT', getenv('APP_ENV') ?: 'development');

$config['base_url']        = getenv('APP_URL') ?: 'http://localhost:8080/';
$config['index_page']      = '';
$config['enable_hooks']    = FALSE;
$config['charset']         = 'UTF-8';
$config['language']        = 'english';

// Routing — modern router runs alongside the CI3-style routes.php.
$config['enable_modern_routing'] = TRUE;
$config['prefer_modern']         = TRUE;
$config['cache_routes']          = ENVIRONMENT === 'production';

// Security.
$config['csrf_protection'] = ENVIRONMENT === 'production';
$config['cookie_prefix']   = '';
$config['cookie_secure']   = ENVIRONMENT === 'production';
$config['cookie_httponly'] = TRUE;

// Sessions.
$config['sess_driver']     = 'files';
$config['sess_save_path']  = getenv('SESS_SAVE_PATH') ?: (APPPATH ?? __DIR__ . '/../../storage') . '/Session';
$config['sess_expiration'] = 7200;
$config['sess_cookie_name'] = 'kodhe_session';

// Logging.
$config['log_threshold']   = ENVIRONMENT === 'production' ? 1 : 3;
$config['log_path']        = (APPPATH ?? __DIR__ . '/../../storage') . '/Logs';

// Database.
$config['hostname'] = getenv('DB_HOST') ?: '127.0.0.1';
$config['username'] = getenv('DB_USERNAME') ?: 'root';
$config['password'] = getenv('DB_PASSWORD') ?: '';
$config['database'] = getenv('DB_DATABASE') ?: 'kodhe_app';
$config['dbdriver'] = getenv('DB_DRIVER') ?: 'mysql';
$config['dbprefix'] = '';
$config['char_set'] = 'utf8';
$config['dbcollat'] = 'utf8_general_ci';

PHP;
    }

    protected function routesStub(): string
    {
        return <<<'PHP'
<?php

/**
 * CI3-style routes (legacy router).
 * The modern router (routes_modern.php) is checked first when
 * $config['prefer_modern'] is TRUE.
 */
$route['default_controller'] = 'Home/index';
$route['404_override']       = '';
$route['translate_uri_dashes'] = FALSE;

PHP;
    }

    protected function modernRoutesStub(): string
    {
        return <<<'PHP'
<?php

/**
 * Modern routing (kodhe/http fluent router).
 * Loaded when $config['enable_modern_routing'] is TRUE.
 */

use Kodhe\Http\Routing\Route;

Route::get('/', 'App\\Controllers\\Home@index');
Route::get('hello/{name}', 'App\\Controllers\\Home@hello')
    ->where('name', '[A-Za-z]+');

PHP;
    }

    protected function homeControllerStub(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Controllers;

class Home
{
    public function index()
    {
        return view('home/index', ['title' => 'Welcome to Kodhe']);
    }

    public function hello(string $name)
    {
        return "Hello, " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "!";
    }
}

PHP;
    }

    protected function baseModelStub(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Models;

use Kodhe\Framework\Database\Model;

class Base extends Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected bool $useTimestamps = true;
    protected array $allowedFields = [];

    public function __construct()
    {
        parent::__construct();
        // $this->setConnection(...) if you need a non-default group.
    }
}

PHP;
    }

    protected function welcomeViewStub(): string
    {
        return <<<PHP
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= \$title ?? 'Kodhe' ?></title>
    <style>
        body { font-family: system-ui, sans-serif; display: grid; place-items: center; min-height: 100vh; margin: 0; background: #0f172a; color: #e2e8f0; }
        .card { background: #1e293b; padding: 3rem 4rem; border-radius: 12px; text-align: center; }
        h1 { margin-top: 0; } code { background: #0f172a; padding: .15rem .5rem; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Kodhe Framework</h1>
        <p>Your application skeleton is ready.</p>
        <p>Edit <code>app/Controllers/Home.php</code> and <code>app/Views/home/index.php</code> to begin.</p>
    </div>
</body>
</html>

PHP;
    }

    protected function readmeStub(string $appName): string
    {
        return <<<MD
# {$appName}

Generated with `php console new {$appName}` (Kodhe Framework).

## Structure

```
app/
  Controllers/   ← App\\Controllers (PascalCase, no underscore)
  Models/        ← App\\Models
  Middleware/    ← App\\Middleware
  Config/        ← config.php, routes.php, routes_modern.php
  Views/         ← templates (PHP native / Blade / Twig via kodhe/view)
public/          ← front controller (index.php)
storage/         ← Cache, Logs, Session, Uploads
database/        ← migrations
console          ← CLI entry point (php console ...)
```

## Getting started

```bash
composer require kodhe/framework   # installs vendor/ + autoload
php console serve                  # http://localhost:8080
```

## Common commands

```bash
php console list                 # all commands
php console make:controller Blog # scaffold App\\Controllers\\Blog
php console make:model Post      # scaffold App\\Models\\Post
php console make:migration create_posts_table
php console new:help             # see `console help new`
```

MD;
    }
}
