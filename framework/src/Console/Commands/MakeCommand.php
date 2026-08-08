<?php

declare(strict_types=0);

namespace Kodhe\Framework\Console\Commands;

/**
 * Make Command - Generate boilerplate code for various components
 */
class MakeCommand extends Command
{
    protected string $name = 'make';
    protected string $description = 'Generate boilerplate code for commands, controllers, models, etc.';
    protected array $usage = [
        'make:command <name>',
        'make:controller <name>',
        'make:model <name>',
        'make:migration <name>',
        'make:middleware <name>',
    ];
    protected array $arguments = [
        'type' => 'Type of component to generate (command, controller, model, migration, middleware)',
        'name' => 'Name of the component to generate',
    ];
    protected array $options = [
        'force' => 'Overwrite existing file',
        'path' => 'Custom output path',
    ];
    protected array $aliases = ['generate', 'g'];

    /**
     * {@inheritdoc}
     */
    public function handle(): int
    {
        $type = $this->argument(0);
        $name = $this->argument(1);

        if (!$type || !$name) {
            $this->error('Missing arguments. Usage: make:<type> <name>');
            $this->writeln('');
            $this->writeln('Available types:');
            $this->writeln('  command     - Create a new console command');
            $this->writeln('  controller  - Create a new controller');
            $this->writeln('  model       - Create a new model');
            $this->writeln('  migration   - Create a new migration');
            $this->writeln('  middleware  - Create a new middleware');
            return 1;
        }

        $method = 'make' . ucfirst($type);
        
        if (method_exists($this, $method)) {
            return $this->$method($name);
        }

        $this->error("Unknown type '{$type}'. Available types: command, controller, model, migration, middleware");
        return 1;
    }

    /**
     * Make a new command
     */
    protected function makeCommand(string $name): int
    {
        $className = basename($name);
        $namespace = 'App\\Console\\Commands';
        $directory = 'app/Console/Commands';
        $filePath = "{$directory}/{$className}.php";

        if (file_exists($filePath) && !$this->option('force')) {
            $this->error("File {$filePath} already exists. Use --force to overwrite.");
            return 1;
        }

        $stub = $this->getCommandStub($className, $namespace);
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($filePath, $stub);
        
        $this->success("Command created: {$filePath}");
        return 0;
    }

    /**
     * Make a new controller
     */
    protected function makeController(string $name): int
    {
        $className = basename($name);
        $namespace = 'App\\Controllers';
        $directory = 'app/Controllers';
        $filePath = "{$directory}/{$className}.php";

        if (file_exists($filePath) && !$this->option('force')) {
            $this->error("File {$filePath} already exists. Use --force to overwrite.");
            return 1;
        }

        $stub = $this->getControllerStub($className, $namespace);
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($filePath, $stub);
        
        $this->success("Controller created: {$filePath}");
        return 0;
    }

    /**
     * Make a new model
     */
    protected function makeModel(string $name): int
    {
        $className = basename($name);
        $namespace = 'App\\Models';
        $directory = 'app/Models';
        $filePath = "{$directory}/{$className}.php";

        if (file_exists($filePath) && !$this->option('force')) {
            $this->error("File {$filePath} already exists. Use --force to overwrite.");
            return 1;
        }

        $stub = $this->getModelStub($className, $namespace);
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($filePath, $stub);
        
        $this->success("Model created: {$filePath}");
        return 0;
    }

    /**
     * Make a new migration
     */
    protected function makeMigration(string $name): int
    {
        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_{$name}.php";
        $directory = 'database/migrations';
        $filePath = "{$directory}/{$fileName}";

        if (file_exists($filePath) && !$this->option('force')) {
            $this->error("File {$filePath} already exists. Use --force to overwrite.");
            return 1;
        }

        $stub = $this->getMigrationStub($name);
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($filePath, $stub);
        
        $this->success("Migration created: {$filePath}");
        return 0;
    }

    /**
     * Make a new middleware
     */
    protected function makeMiddleware(string $name): int
    {
        $className = basename($name);
        $namespace = 'App\\Middleware';
        $directory = 'app/Middleware';
        $filePath = "{$directory}/{$className}.php";

        if (file_exists($filePath) && !$this->option('force')) {
            $this->error("File {$filePath} already exists. Use --force to overwrite.");
            return 1;
        }

        $stub = $this->getMiddlewareStub($className, $namespace);
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($filePath, $stub);
        
        $this->success("Middleware created: {$filePath}");
        return 0;
    }

    /**
     * Get command stub
     */
    protected function getCommandStub(string $className, string $namespace): string
    {
        return <<<PHP
<?php

declare(strict_types=0);

namespace {$namespace};

use Kodhe\\Framework\\Console\\Command;
use Kodhe\\Framework\\Console\\Input;
use Kodhe\\Framework\\Console\\Output;

class {$className} extends Command
{
    protected string \$name = 'app:' . strtolower('{$className}');
    protected string \$description = 'Description of the command';
    protected array \$arguments = [];
    protected array \$options = [];

    public function handle(): int
    {
        \$this->info('Executing {$className}...');
        
        // Your command logic here
        
        \$this->success('Command completed successfully!');
        return 0;
    }
}

PHP;
    }

    /**
     * Get controller stub
     */
    protected function getControllerStub(string $className, string $namespace): string
    {
        return <<<PHP
<?php

declare(strict_types=0);

namespace {$namespace};

class {$className}
{
    public function index()
    {
        // Your logic here
    }
}

PHP;
    }

    /**
     * Get model stub
     */
    protected function getModelStub(string $className, string $namespace): string
    {
        return <<<PHP
<?php

declare(strict_types=0);

namespace {$namespace};

class {$className}
{
    // Define your model properties and methods
}

PHP;
    }

    /**
     * Get migration stub
     */
    protected function getMigrationStub(string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=0);

return new class {
    public function up(): void
    {
        // Create table or add columns
    }

    public function down(): void
    {
        // Drop table or remove columns
    }
};

PHP;
    }

    /**
     * Get middleware stub
     */
    protected function getMiddlewareStub(string $className, string $namespace): string
    {
        return <<<PHP
<?php

declare(strict_types=0);

namespace {$namespace};

class {$className}
{
    public function handle(\$request, callable \$next)
    {
        // Before request
        
        \$response = \$next(\$request);
        
        // After request
        
        return \$response;
    }
}

PHP;
    }
}
