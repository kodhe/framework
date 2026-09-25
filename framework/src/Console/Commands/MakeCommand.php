<?php

declare(strict_types=1);

namespace Kodhe\Framework\Console\Commands;

use Kodhe\Framework\Console\Command;
use Kodhe\Framework\Console\Console;

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
        'make:crud <name>',
    ];
    protected array $arguments = [
        'type' => 'Type of component to generate (command, controller, model, migration, middleware, crud)',
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

        // Support the sub-command style invocation: `console make:controller Foo`.
        // When dispatched via a "make:<type>" alias, argument 0 is the literal
        // command name and the real component name shifts one position right.
        if (is_string($type) && str_starts_with($type, 'make:')) {
            $name = $name ?: $this->argument(2);
            $type = substr($type, strlen('make:'));
        } elseif ($type === 'make') {
            // `console make controller Foo`
            $type = $name;
            $name = $this->argument(2);
        }

        if (!$type || !$name) {
            $this->error('Missing arguments. Usage: make:<type> <name>');
            $this->writeln('');
            $this->writeln('Available types:');
            $this->writeln('  command     - Create a new console command');
            $this->writeln('  controller  - Create a new controller');
            $this->writeln('  model       - Create a new model');
            $this->writeln('  migration   - Create a new migration');
            $this->writeln('  middleware  - Create a new middleware');
            $this->writeln('  crud        - Create a full CRUD stack (model, controller, migration, views)');
            return 1;
        }

        $method = 'make' . ucfirst($type);
        
        if (method_exists($this, $method)) {
            return $this->$method($name);
        }

        $this->error("Unknown type '{$type}'. Available types: command, controller, model, migration, middleware, crud");
        return 1;
    }

    /**
     * Make a full CRUD stack: model, controller, migration and views.
     *
     * Style-nya mengikuti generator CRUD pada framework modern (Laravel/Craft),
     * tetapi hasil generate tetap kompatibel dengan Kodhe Framework:
     *   - Model  : Kodhe\Framework\Database\Model (CI3-style query builder)
     *   - Controller : extends Kodhe\Framework\Http\Controllers\BaseController
     *   - Migration  : anonymous class up()/down()
     *   - Views      : CI3-style view files (index/create/edit/show)
     */
    protected function makeCrud(string $name): int
    {
        $className = basename(str_replace('\\', '/', $name));

        if ($className === '' || !preg_match('/^[A-Z][A-Za-z0-9_]*$/', $className)) {
            $this->error('CRUD name must be an UpperCamelCase resource name, e.g. "Post" or "Product".');
            return 1;
        }

        // Optional field list: php console make:crud Post title:string body:text published:bool
        $rawArgs = array_values(array_map('strval', $_SERVER['argv'] ?? []));
        $fieldArgs = [];
        foreach ($rawArgs as $i => $arg) {
            if ($i === 0) {
                continue; // script name
            }
            if ($arg === 'make' || $arg === 'g' || $arg === 'generate' || str_starts_with($arg, 'make:') || $arg === 'crud') {
                continue; // command token (any invocation style)
            }
            if (str_starts_with($arg, '-')) {
                continue; // options like --force
            }
            if (strtolower($arg) === strtolower($className)) {
                continue; // the resource name itself
            }
            $fieldArgs[] = $arg;
        }
        $fields = $fieldArgs;

        $singularLower = strtolower($className);
        $pluralSnake = $this->snakeCase($this->pluralize($className));
        $resourceSegment = strtolower($this->kebabCase($this->pluralize($className)));

        $files = [];

        // 1) Model
        $modelDir = 'app/Models';
        $modelPath = "{$modelDir}/{$className}.php";
        $files[] = [$modelPath, $this->getCrudModelStub($className, $pluralSnake)];

        // 2) Controller
        $controllerDir = 'app/Controllers';
        $controllerPath = "{$controllerDir}/{$className}.php";
        $files[] = [$controllerPath, $this->getCrudControllerStub($className, $pluralSnake, $singularLower, $resourceSegment)];

        // 3) Migration
        $timestamp = date('Y_m_d_His');
        $migrationPath = "database/migrations/{$timestamp}_create_{$pluralSnake}_table.php";
        $files[] = [$migrationPath, $this->getCrudMigrationStub($pluralSnake, $fields)];

        // 4) Views (CI3 style)
        $viewDir = "app/Views/{$resourceSegment}";
        foreach (['index', 'create', 'edit', 'show', '_form'] as $view) {
            $files[] = ["{$viewDir}/{$view}.php", $this->getCrudViewStub($view, $className, $singularLower, $resourceSegment)];
        }

        // Conflict check first (atomic: don't write anything if one file exists).
        if (!$this->option('force')) {
            foreach ($files as [$path, $stub]) {
                if (file_exists($path)) {
                    $this->error("File {$path} already exists. Use --force to overwrite.");
                    return 1;
                }
            }
        }

        foreach ($files as [$path, $stub]) {
            $directory = dirname($path);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            file_put_contents($path, $stub);
            $this->success("Created: {$path}");
        }

        $this->writeln('');
        $this->info("CRUD \"{$className}\" generated successfully!");
        $this->writeln("Next steps:");
        $this->writeln("  1. Run the migration:  php console migrate (or load it manually)");
        $this->writeln("  2. Add routes for /{$resourceSegment} to your routing config.");
        return 0;
    }

    /**
     * Convert PascalCase/camelCase to snake_case.
     */
    private function snakeCase(string $input): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }

    /**
     * Convert PascalCase/camelCase to kebab-case.
     */
    private function kebabCase(string $input): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $input));
    }

    /**
     * Naive English pluralizer for resource names.
     */
    private function pluralize(string $word): string
    {
        if ($word === '') {
            return $word;
        }
        if (preg_match('/(s|x|z|ch|sh)$/i', $word)) {
            return $word . 'es';
        }
        if (preg_match('/[^aeiou]y$/i', $word)) {
            return substr($word, 0, -1) . 'ies';
        }
        return $word . 's';
    }

    /**
     * Parse "name:type" field definitions for the migration stub.
     *
     * @return array<int, array{0:string,1:string}>
     */
    private function parseCrudFields(array $rawFields): array
    {
        $known = ['string', 'text', 'integer', 'int', 'boolean', 'bool', 'date', 'datetime', 'decimal'];
        $fields = [];
        foreach ($rawFields as $raw) {
            $parts = explode(':', $raw, 2);
            $fieldName = preg_replace('/[^a-z0-9_]/', '', strtolower($parts[0] ?? ''));
            if ($fieldName === '') {
                continue;
            }
            $fieldType = $parts[1] ?? 'string';
            if (!in_array($fieldType, $known, true)) {
                $fieldType = 'string';
            }
            $fields[] = [$fieldName, $fieldType];
        }
        if ($fields === []) {
            $fields[] = ['name', 'string'];
        }
        return $fields;
    }

    /**
     * CRUD model stub — extends Kodhe ORM Model (CI3-compatible query builder).
     */
    protected function getCrudModelStub(string $className, string $table): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\\Models;

use Kodhe\\Framework\\Database\\Model;

class {$className} extends Model
{
    protected \$table = '{$table}';

    protected \$primaryKey = 'id';

    protected \$returnType = 'object';

    protected \$useTimestamps = true;

    protected \$allowedFields = [];
}

PHP;
    }

    /**
     * CRUD controller stub — full resource actions (index/create/store/edit/update/delete).
     */
    protected function getCrudControllerStub(string $className, string $pluralSnake, string $singularLower, string $resourceSegment): string
    {
        $modelVar = '$' . $singularLower . 'Model';
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\\Controllers;

use Kodhe\\Framework\\Http\\Controllers\\BaseController;
use App\\Models\\{$className};

/**
 * {$className} CRUD controller.
 *
 * Generated by: php console make:crud {$className}
 */
class {$className} extends BaseController
{
    protected {$modelVar};

    public function __construct()
    {
        parent::__construct();
        {$modelVar} = new {$className}();
        \$this->{$singularLower}Model = {$modelVar};
    }

    /**
     * GET /{$resourceSegment} — list all resources.
     */
    public function index()
    {
        \$data['{$pluralSnake}'] = \$this->{$singularLower}Model->orderBy('id', 'DESC')->all();
        \$this->load->view('{$resourceSegment}/index', \$data);
    }

    /**
     * GET /{$resourceSegment}/create — show create form.
     */
    public function create()
    {
        \$this->load->view('{$resourceSegment}/create');
    }

    /**
     * POST /{$resourceSegment} — persist a new resource.
     */
    public function store()
    {
        \$input = \$this->input->post(NULL, TRUE);

        if (\$this->{$singularLower}Model->insert(\$input)) {
            redirect('{$resourceSegment}');
        }

        \$data['error'] = \$this->{$singularLower}Model->errors();
        \$this->load->view('{$resourceSegment}/create', \$data);
    }

    /**
     * GET /{$resourceSegment}/show/{id} — display a single resource.
     */
    public function show(\$id)
    {
        \${$singularLower} = \$this->{$singularLower}Model->find(\$id);

        if (\$this->{$singularLower}Model->errors()) {
            show_404();
        }

        \$this->load->view('{$resourceSegment}/show', ['{$singularLower}' => \${$singularLower}]);
    }

    /**
     * GET /{$resourceSegment}/edit/{id} — show edit form.
     */
    public function edit(\$id)
    {
        \${$singularLower} = \$this->{$singularLower}Model->find(\$id);

        if (\$this->{$singularLower}Model->errors()) {
            show_404();
        }

        \$this->load->view('{$resourceSegment}/edit', ['{$singularLower}' => \${$singularLower}]);
    }

    /**
     * POST /{$resourceSegment}/update/{id} — persist changes.
     */
    public function update(\$id)
    {
        \$input = \$this->input->post(NULL, TRUE);

        if (\$this->{$singularLower}Model->update(\$input, ['id' => \$id])) {
            redirect('{$resourceSegment}');
        }

        \$data['error'] = \$this->{$singularLower}Model->errors();
        \$data['{$singularLower}'] = \$this->{$singularLower}Model->find(\$id);
        \$this->load->view('{$resourceSegment}/edit', \$data);
    }

    /**
     * POST /{$resourceSegment}/delete/{id} — remove a resource.
     */
    public function delete(\$id)
    {
        \$this->{$singularLower}Model->delete(\$id);
        redirect('{$resourceSegment}');
    }
}

PHP;
    }

    /**
     * CRUD migration stub with parsed fields.
     */
    protected function getCrudMigrationStub(string $table, array $rawFields): string
    {
        $fields = $this->parseCrudFields($rawFields);
        $columnLines = '';
        foreach ($fields as [$fieldName, $fieldType]) {
            $phpType = match ($fieldType) {
                'integer', 'int' => 'int',
                'boolean', 'bool' => 'bool',
                'text' => 'string',
                default => 'string',
            };
            $columnLines .= "            '{$fieldName}' => ['type' => '{$phpType}', 'constraint' => " . ($phpType === 'string' ? '255' : 'NULL') . ", 'null' => true],\n";
        }

        $columnComment = [];
        foreach ($fields as [$fieldName, $fieldType]) {
            $phpType = match ($fieldType) {
                'integer', 'int' => 'int',
                'boolean', 'bool' => 'bool',
                default => 'string',
            };
            $constraint = $phpType === 'string' ? '255' : 'NULL';
            $columnComment[] = "        //     '{$fieldName}' => ['type' => '{$phpType}', 'constraint' => {$constraint}, 'null' => true],";
        }
        $columnList = implode("\n", $columnComment);

        return "<?php\n\ndeclare(strict_types=1);\n\nreturn new class {\n    public function up(): void\n    {\n        \$schema = CI::load_dbutil();\n        \$schema->create_table('{$table}', [\n{$columnList}\n        //     'created_at' => ['type' => 'datetime', 'null' => true],\n        //     'updated_at' => ['type' => 'datetime', 'null' => true],\n        // ]);
    }\n\n    public function down(): void\n    {\n        \$schema = CI::load_dbutil();\n        \$schema->drop_table('{$table}');\n    }\n};\n";
    }

    /**
     * CRUD view stubs — plain PHP, CI3/native style (no Blade required).
     */
    protected function getCrudViewStub(string $view, string $className, string $singularLower, string $resourceSegment): string
    {
        $title = trim(preg_replace('/(?<!^)[A-Z]/', ' $0', $className) ?? $className);

        return match ($view) {
            'index' => <<<HTML
<!-- {$title} listing — generated by make:crud -->
<h1>{$title}</h1>
<p><a href="<?= base_url('{$resourceSegment}/create') ?>">+ Add New</a></p>

<table border="1" cellpadding="6">
    <thead>
        <tr>
            <th>ID</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach (\${$this->camelPlural($className)} as \${$singularLower}): ?>
        <tr>
            <td><?= htmlspecialchars((string) \${$singularLower}->id, ENT_QUOTES) ?></td>
            <td>
                <a href="<?= base_url('{$resourceSegment}/show/' . \${$singularLower}->id) ?>">Show</a> |
                <a href="<?= base_url('{$resourceSegment}/edit/' . \${$singularLower}->id) ?>">Edit</a> |
                <form action="<?= base_url('{$resourceSegment}/delete/' . \${$singularLower}->id) ?>" method="post" style="display:inline" onsubmit="return confirm('Delete?')">
                    <button type="submit">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

HTML,
            'create' => <<<HTML
<!-- Create {$title} — generated by make:crud -->
<h1>New {$title}</h1>
<?php if (!empty(\$error)): ?>
    <pre><?= print_r(\$error, true) ?></pre>
<?php endif; ?>

<form action="<?= base_url('{$resourceSegment}') ?>" method="post">
    <?php include __DIR__ . '/_form.php'; ?>
    <button type="submit">Save</button>
</form>
<a href="<?= base_url('{$resourceSegment}') ?>">Back to list</a>

HTML,
            'edit' => <<<HTML
<!-- Edit {$title} — generated by make:crud -->
<h1>Edit {$title}</h1>
<?php if (!empty(\$error)): ?>
    <pre><?= print_r(\$error, true) ?></pre>
<?php endif; ?>

<form action="<?= base_url('{$resourceSegment}/update/' . \${$singularLower}->id) ?>" method="post">
    <?php include __DIR__ . '/_form.php'; ?>
    <button type="submit">Update</button>
</form>
<a href="<?= base_url('{$resourceSegment}') ?>">Back to list</a>

HTML,
            'show' => <<<HTML
<!-- Show {$title} — generated by make:crud -->
<h1>{$title} #<?= \${$singularLower}->id ?></h1>
<pre><?= print_r(\${$singularLower}, true) ?></pre>
<a href="<?= base_url('{$resourceSegment}/edit/' . \${$singularLower}->id) ?>">Edit</a> |
<a href="<?= base_url('{$resourceSegment}') ?>">Back to list</a>

HTML,
            '_form' => <<<HTML
<!-- Shared form fields for {$title} — customize me after generation -->
<div>
    <label>Name</label>
    <input type="text" name="name" value="<?= htmlspecialchars((string) (\${$singularLower}->name ?? ''), ENT_QUOTES) ?>">
</div>

HTML,
            default => '',
        };
    }

    /**
     * "ProductTag" -> "productTags" (for view loop variables).
     */
    private function camelPlural(string $className): string
    {
        return lcfirst($this->pluralize($className));
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

declare(strict_types=1);

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

declare(strict_types=1);

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

declare(strict_types=1);

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

declare(strict_types=1);

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

declare(strict_types=1);

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
