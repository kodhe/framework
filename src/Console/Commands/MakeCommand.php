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
        'make:crud <name> [field:type ...] [--style=kodhe|ci3|both] [--engine=php|blade]',
    ];
    protected array $arguments = [
        'type' => 'Type of component to generate (command, controller, model, migration, middleware, crud)',
        'name' => 'Name of the component to generate',
    ];
    protected array $options = [
        'force' => 'Overwrite existing file',
        'path' => 'Custom output path',
        'style' => 'CRUD routing style: kodhe (modern Route::resource), ci3 ($route[...] legacy), or both (default)',
        'engine' => 'CRUD view engine: php (CI3/native, default) or blade',
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
            $this->writeln('  crud        - Create a full CRUD stack (model, controller, migration, views, routes)');
            $this->writeln('');
            $this->writeln('CRUD options:');
            $this->writeln('  --style=kodhe|ci3|both   Route generation style (default: both)');
            $this->writeln('      kodhe : modern fluent routes -> app/Config/routes_modern.php (Route::resource)');
            $this->writeln('      ci3   : legacy CI3 routes    -> app/Config/routes.php ($route[...])');
            $this->writeln('  --engine=php|blade       View engine for generated views (default: php)');
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
     * Make a full CRUD stack: model, controller, migration, views and routes.
     *
     * Style-nya mengikuti generator CRUD pada framework modern (Laravel/Craft),
     * tetapi hasil generate tetap kompatibel dengan Kodhe Framework:
     *   - Model  : Kodhe\Framework\Database\Model (CI3-style query builder)
     *   - Controller : extends Kodhe\Framework\Http\Controllers\BaseController
     *   - Migration  : anonymous class up()/down() via Loader::dbforge() (modern stack)
     *   - Views      : engine bisa dipilih (--engine=php|blade)
     *   - Routes     : --style=kodhe  -> app/Config/routes_modern.php (Route::resource)
     *                  --style=ci3    -> app/Config/routes.php ($route[...] legacy CI3)
     *                  --style=both   -> keduanya (default)
     */
    protected function makeCrud(string $name): int
    {
        $className = basename(str_replace('\\', '/', $name));

        if ($className === '' || !preg_match('/^[A-Z][A-Za-z0-9_]*$/', $className)) {
            $this->error('CRUD name must be an UpperCamelCase resource name, e.g. "Post" or "Product".');
            return 1;
        }

        // Routing style: kodhe (modern fluent) | ci3 (legacy $route[...]) | both (default)
        $style = strtolower((string) $this->option('style', 'both'));
        if (!in_array($style, ['kodhe', 'ci3', 'both'], true)) {
            $this->error("Unknown --style '{$style}'. Use: kodhe, ci3, or both.");
            return 1;
        }

        // View engine: php (CI3/native, default) | blade
        $engine = strtolower((string) $this->option('engine', 'php'));
        if (!in_array($engine, ['php', 'blade'], true)) {
            $this->error("Unknown --engine '{$engine}'. Use: php or blade.");
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
                continue; // options like --force / --style=ci3 / --engine=blade
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
        // Controller memakai suffix "Controller" agar tidak bentrok dengan nama
        // Model saat kedua class di-autoload dalam satu request (CI3-style).
        $controllerClass = $className . 'Controller';
        $viewSuffix = $engine === 'blade' ? '.blade.php' : '.php';

        $files = [];

        // 1) Model
        $modelDir = 'app/Models';
        $modelPath = "{$modelDir}/{$className}.php";
        $files[] = [$modelPath, $this->getCrudModelStub($className, $pluralSnake, $fields)];

        // 2) Controller
        $controllerDir = 'app/Controllers';
        $controllerPath = "{$controllerDir}/{$controllerClass}.php";
        $files[] = [$controllerPath, $this->getCrudControllerStub($className, $controllerClass, $pluralSnake, $singularLower, $resourceSegment, $viewSuffix)];

        // 3) Migration
        $timestamp = date('Y_m_d_His');
        $migrationPath = "database/migrations/{$timestamp}_create_{$pluralSnake}_table.php";
        $files[] = [$migrationPath, $this->getCrudMigrationStub($pluralSnake, $fields)];

        // 4) Views (engine sesuai pilihan --engine)
        $viewDir = "app/Views/{$resourceSegment}";
        $parsedFields = $this->parseCrudFields($fields);
        foreach (['index', 'create', 'edit', 'show', '_form'] as $view) {
            $fileName = $view . ($engine === 'blade' ? '.blade.php' : '.php');
            $files[] = ["{$viewDir}/{$fileName}", $this->getCrudViewStub($view, $className, $singularLower, $resourceSegment, $engine, $parsedFields)];
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

        // 5) Routes — otomatis, tanpa perlu edit manual.
        $routesWritten = [];
        if ($style === 'kodhe' || $style === 'both') {
            $written = $this->appendModernRoutes($resourceSegment, $controllerClass);
            if ($written !== null) {
                $routesWritten[] = $written;
            }
        }
        if ($style === 'ci3' || $style === 'both') {
            $written = $this->appendCi3Routes($resourceSegment, $className);
            if ($written !== null) {
                $routesWritten[] = $written;
            }
        }

        $this->writeln('');
        $this->info("CRUD \"{$className}\" generated successfully!");
        $this->writeln("Next steps:");
        $this->writeln("  1. Run the migration:  php console migrate (or load it manually)");
        if ($routesWritten === []) {
            $this->writeln("  2. Routes NOT written (routing files not found). Add routes for /{$resourceSegment} manually.");
        } else {
            $this->writeln("  2. Routes registered in: " . implode(', ', $routesWritten));
        }
        return 0;
    }

    /**
     * Append modern Kodhe-style routes (fluent Route::resource) to
     * app/Config/routes_modern.php. Creates the file from scratch when missing.
     * Uses markers so re-running with --force stays idempotent.
     */
    private function appendModernRoutes(string $resourceSegment, string $controllerClass): ?string
    {
        $path = 'app/Config/routes_modern.php';
        $marker = "/* make:crud:{$resourceSegment} (modern) */";

        if (!file_exists($path)) {
            if (!is_dir('app/Config')) {
                if (!is_dir('app')) {
                    return null; // no app skeleton at all -> skip, user informed
                }
                mkdir('app/Config', 0755, true);
            }
            $contents = "<?php\n\n/**\n * Modern routing (kodhe/http fluent router).\n * Loaded when \$config['enable_modern_routing'] is TRUE.\n */\n\nuse Kodhe\\Http\\Routing\\Route;\n\n";
        } else {
            $contents = (string) file_get_contents($path);
        }

        if (strpos($contents, $marker) !== false) {
            $this->success("Routes already present: {$path} ({$resourceSegment})");
            return $path;
        }

        // Route::resource (kodhe/http) membangun handler "Controller@method"
        // per aksi; opsi valid: only/except/names/parameters. Controller hasil
        // generate memakai method `delete()` (konvensi CI3), sedangkan resource
        // modern memanggil `destroy()` — jadi destroy di-exclude dan digantikan
        // satu route DELETE manual yang menunjuk langsung ke @delete.
        // Catatan edit: resource mendaftarkan PUT /{res}/{id}; view hasil
        // generate memakai form POST ke /{res}/update/{id}, jadi rute tambahan
        // itu didaftarkan eksplisit di bawah. store tetap POST /{res} standar.
        // Placeholder parameter {articles} dibangun terpisah agar tidak tertafsir
        // sebagai ekspresi variabel PHP saat stub digenerate.
        $lc = '{' . $resourceSegment . '}';
        $block = "\n{$marker}\n"
            . "Route::resource('{$resourceSegment}', 'App\\\\Controllers\\\\{$controllerClass}', ['except' => ['destroy']]);\n"
            . "Route::post('/{$resourceSegment}/update/{$lc}', 'App\\\\Controllers\\\\{$controllerClass}@update')->name('{$resourceSegment}.update');\n"
            . "Route::delete('/{$resourceSegment}/{$lc}', 'App\\\\Controllers\\\\{$controllerClass}@delete')->name('{$resourceSegment}.destroy');\n";
        file_put_contents($path, rtrim($contents) . "\n" . $block);
        $this->success("Updated: {$path} (Route::resource('{$resourceSegment}'))");
        return $path;
    }

    /**
     * Append CI3-style legacy routes ($route[...] map) to
     * app/Config/routes.php. Creates the file from scratch when missing.
     */
    private function appendCi3Routes(string $resourceSegment, string $className): ?string
    {
        $path = 'app/Config/routes.php';
        $marker = "/* make:crud:{$resourceSegment} (ci3) */";

        if (!file_exists($path)) {
            if (!is_dir('app/Config')) {
                if (!is_dir('app')) {
                    return null;
                }
                mkdir('app/Config', 0755, true);
            }
            $contents = "<?php\n\n/**\n * CI3-style routes (legacy router).\n */\n\$route['default_controller'] = 'Home/index';\n\$route['404_override'] = '';\n\$route['translate_uri_dashes'] = FALSE;\n";
        } else {
            $contents = (string) file_get_contents($path);
        }

        if (strpos($contents, $marker) !== false) {
            $this->success("Routes already present: {$path} ({$resourceSegment})");
            return $path;
        }

        $lower = strtolower($className);
        // Catatan: router CI3 memetakan berdasarkan URI (satu array $route), jadi
        // GET/POST pada URI yang sama ditangani di controller (cek is_post()).
        // Pola URI harus identik dengan view hasil generate: form create POST ke
        // /{res}/store, form edit POST ke /{res}/update/{id}, tombol hapus POST
        // ke /{res}/delete/{id}.
        $block = <<<PHP

{$marker}
\$route['{$resourceSegment}']                 = '{$lower}/index';
\$route['{$resourceSegment}/create']          = '{$lower}/create';
\$route['{$resourceSegment}/store']           = '{$lower}/store';
\$route['{$resourceSegment}/show/([0-9]+)']   = '{$lower}/show/$1';
\$route['{$resourceSegment}/edit/([0-9]+)']   = '{$lower}/edit/$1';
\$route['{$resourceSegment}/update/([0-9]+)'] = '{$lower}/update/$1';
\$route['{$resourceSegment}/delete/([0-9]+)'] = '{$lower}/delete/$1';
PHP;
        file_put_contents($path, rtrim($contents) . "\n" . $block . "\n");
        $this->success("Updated: {$path} (\$route['{$resourceSegment}/...'])");
        return $path;
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
     * "title" -> "Title", "created_at" -> "Created At" (label UI).
     */
    private function labelize(string $field): string
    {
        return ucwords(str_replace('_', ' ', $field));
    }

    /**
     * Sel header & sel body tabel listing CRUD dari daftar field terparse.
     *
     * @param  array<int, array{0:string,1:string}> $fields
     * @return array{0:string,1:string} [headCells, bodyCells] (sudah termasuk newline)
     */
    private function crudIndexColumns(array $fields, string $engine, string $rowVar, int $maxCols = 4): array
    {
        $cols = array_slice($fields, 0, $maxCols);
        $head = '';
        $body = '';
        foreach ($cols as [$name, $type]) {
            $label = $this->labelize($name);
            $head .= "            <th>{$label}</th>\n";
            if ($engine === 'blade') {
                $body .= "            <td>{{ \${$rowVar}->{$name} }}</td>\n";
            } else {
                $body .= "            <td><?= htmlspecialchars((string) (\${$rowVar}->{$name} ?? ''), ENT_QUOTES) ?></td>\n";
            }
        }
        return [$head, $body];
    }

    /**
     * Input form (_form.php / _form.blade.php) dari daftar field terparse:
     * text->input, textarea-ish (text) -> <textarea>, bool -> select Ya/Tidak,
     * date/datetime/integer/decimal -> input tipe sesuai.
     *
     * @param  array<int, array{0:string,1:string}> $fields
     */
    private function crudFormInputs(array $fields, string $engine, string $varName): string
    {
        $out = '';
        foreach ($fields as [$name, $type]) {
            $label = $this->labelize($name);
            if ($engine === 'blade') {
                $val = "{{ \${$varName}->{$name} ?? '' }}";
                switch ($type) {
                    case 'text':
                        $out .= "<div>\n    <label>{$label}</label>\n    <textarea name=\"{$name}\">{$val}</textarea>\n</div>\n";
                        break;
                    case 'boolean':
                    case 'bool':
                        $out .= "<div>\n    <label>{$label}</label>\n    <select name=\"{$name}\">\n        <option value=\"1\">Ya</option>\n        <option value=\"0\">Tidak</option>\n    </select>\n</div>\n";
                        break;
                    default:
                        $htmlType = match ($type) {
                            'integer', 'int' => 'number',
                            'decimal' => 'number',
                            'date' => 'date',
                            'datetime' => 'datetime-local',
                            default => 'text',
                        };
                        $step = $type === 'decimal' ? ' step="0.01"' : '';
                        $out .= "<div>\n    <label>{$label}</label>\n    <input type=\"{$htmlType}\"{$step} name=\"{$name}\" value=\"{$val}\">\n</div>\n";
                }
                continue;
            }

            // engine php (CI3/native) — selalu escape nilai lama
            $esc = "<?php \$__v = (string) (\${$varName}->{$name} ?? ''); echo htmlspecialchars(\$__v, ENT_QUOTES); ?>";
            switch ($type) {
                case 'text':
                    $out .= "<div>\n    <label>{$label}</label>\n    <textarea name=\"{$name}\">{$esc}</textarea>\n</div>\n";
                    break;
                case 'boolean':
                case 'bool':
                    $out .= "<div>\n    <label>{$label}</label>\n    <?php \$__sel = !empty(\${$varName}->{$name} ?? null); ?>\n    <select name=\"{$name}\">\n        <option value=\"1\" <?= \$__sel ? 'selected' : '' ?>>Ya</option>\n        <option value=\"0\" <?= \$__sel ? '' : 'selected' ?>>Tidak</option>\n    </select>\n</div>\n";
                    break;
                default:
                    $htmlType = match ($type) {
                        'integer', 'int' => 'number',
                        'decimal' => 'number',
                        'date' => 'date',
                        'datetime' => 'datetime-local',
                        default => 'text',
                    };
                    $step = $type === 'decimal' ? ' step="0.01"' : '';
                    $out .= "<div>\n    <label>{$label}</label>\n    <input type=\"{$htmlType}\"{$step} name=\"{$name}\" value=\"{$esc}\">\n</div>\n";
            }
        }
        return $out;
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
     *
     * $allowedFields diisi otomatis dari daftar field make:crud
     * (kecuali 'id' — primary key tidak boleh mass-assignable).
     */
    protected function getCrudModelStub(string $className, string $table, array $rawFields = []): string
    {
        $fields = $this->parseCrudFields($rawFields);
        $assignable = array_values(array_filter(array_column($fields, 0), fn($f) => $f !== 'id'));
        $allowedList = implode(', ', array_map(fn($f) => "'{$f}'", $assignable));

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

    protected \$allowedFields = [{$allowedList}];
}

PHP;
    }

    /**
     * CRUD controller stub — full resource actions (index/create/store/edit/update/delete).
     *
     * Nama class memakai suffix "Controller" agar tidak bentrok dengan Model
     * bernama sama saat keduanya di-autoload dalam satu request.
     */
    protected function getCrudControllerStub(string $className, string $controllerClass, string $pluralSnake, string $singularLower, string $resourceSegment, string $viewSuffix = '.php'): string
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
class {$controllerClass} extends BaseController
{
    protected {$modelVar};

    public function __construct()
    {
        parent::__construct();
        \$this->{$singularLower}Model = new {$className}();
    }

    /**
     * GET /{$resourceSegment} — list all resources.
     */
    public function index()
    {
        \$data['{$pluralSnake}'] = \$this->{$singularLower}Model->orderBy('id', 'DESC')->all();
        \$this->load->view('{$resourceSegment}/index{$viewSuffix}', \$data);
    }

    /**
     * GET /{$resourceSegment}/create — show create form.
     */
    public function create()
    {
        \$this->load->view('{$resourceSegment}/create{$viewSuffix}');
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
        \$this->load->view('{$resourceSegment}/create{$viewSuffix}', \$data);
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

        \$this->load->view('{$resourceSegment}/show{$viewSuffix}', ['{$singularLower}' => \${$singularLower}]);
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

        \$this->load->view('{$resourceSegment}/edit{$viewSuffix}', ['{$singularLower}' => \${$singularLower}]);
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
        \$this->load->view('{$resourceSegment}/edit{$viewSuffix}', \$data);
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
     *
     * Uses the modern Kodhe Database stack (Loader::dbforge() -> driver-level
     * Forge class), NOT the legacy CI3 dbutil. The ORM CI3-style layer is
     * kept only as legacy compatibility; new generated code must target the
     * refactored component.
     */
    protected function getCrudMigrationStub(string $table, array $rawFields): string
    {
        $fields = $this->parseCrudFields($rawFields);

        $columnLines = "            'id' => ['type' => 'INT', 'constraint' => 9, 'unsigned' => TRUE, 'auto_increment' => TRUE],\n";
        foreach ($fields as [$fieldName, $fieldType]) {
            [$forgeType, $constraint] = match ($fieldType) {
                'integer', 'int' => ['INT', '9'],
                'boolean', 'bool' => ['TINYINT', '1'],
                'text' => ['TEXT', null],
                default => ['VARCHAR', '255'],
            };
            $typePart = "'type' => '{$forgeType}'";
            if ($constraint !== null) {
                $typePart .= ", 'constraint' => {$constraint}";
            }
            $columnLines .= "            '{$fieldName}' => [{$typePart}, 'null' => TRUE],\n";
        }
        $columnLines .= "            'created_at' => ['type' => 'DATETIME', 'null' => TRUE],\n";
        $columnLines .= "            'updated_at' => ['type' => 'DATETIME', 'null' => TRUE],\n";

        return <<<PHP
<?php

declare(strict_types=1);

use Kodhe\\Framework\\Database\\Loader;

/**
 * Migration: create {$table} table.
 *
 * Generated by `php console make:crud` — uses the modern Kodhe Database
 * component (driver-level Forge via Loader::dbforge()), not the legacy
 * CI3 dbutil.
 */
return new class {

    public function up(): void
    {
        // Ambil forge dari koneksi aktif via Kodhe Database Loader (modern stack).
        \$forge = Loader::dbforge(null, true);

        \$forge->add_field([
{$columnLines}        ]);

        \$forge->add_key('id', TRUE);
        \$forge->create_table('{$table}', TRUE);
    }

    public function down(): void
    {
        \$forge = Loader::dbforge(null, true);
        \$forge->drop_table('{$table}', TRUE);
    }
};

PHP;
    }

    /**
     * CRUD view stubs — engine bisa dipilih:
     *   php   : plain PHP, CI3/native style (default)
     *   blade : template Blade (.blade.php), dipakai ViewFactory Kodhe
     */
    protected function getCrudViewStub(string $view, string $className, string $singularLower, string $resourceSegment, string $engine = 'php', array $parsedFields = []): string
    {
        if ($engine === 'blade') {
            return $this->getCrudBladeViewStub($view, $className, $singularLower, $resourceSegment, $parsedFields);
        }

        $title = trim(preg_replace('/(?<!^)[A-Z]/', ' $0', $className) ?? $className);

        // Kolom tabel listing & field form diambil dari daftar field make:crud,
        // sehingga view hasil generate langsung memakai kolom nyata (bukan
        // placeholder 'name' yang harus diedit manual lagi).
        [$headCells, $bodyCells] = $this->crudIndexColumns($parsedFields, 'php', $singularLower);
        $formInputs = $this->crudFormInputs($parsedFields, 'php', $singularLower);

        return match ($view) {
            'index' => <<<HTML
<!-- {$title} listing — generated by make:crud -->
<h1>{$title}</h1>
<p><a href="<?= base_url('{$resourceSegment}/create') ?>">+ Add New</a></p>

<table border="1" cellpadding="6">
    <thead>
        <tr>
            <th>ID</th>
{$headCells}            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach (\${$this->camelPlural($className)} as \${$singularLower}): ?>
        <tr>
            <td><?= htmlspecialchars((string) \${$singularLower}->id, ENT_QUOTES) ?></td>
{$bodyCells}            <td>
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
<!-- Shared form fields for {$title} — generated by make:crud (lihat field di bawah) -->
{$formInputs}
HTML,
            default => '',
        };
    }

    /**
     * CRUD view stubs — Blade engine (.blade.php).
     *
     * Dipakai saat `make:crud ... --engine=blade`. Controller hasil generate
     * otomatis merujuk nama view dengan ekstensi `.blade.php` sehingga
     * ViewFactory Kodhe (default engine: blade) me-resolve engine yang benar.
     */
    protected function getCrudBladeViewStub(string $view, string $className, string $singularLower, string $resourceSegment, array $parsedFields = []): string
    {
        $title = trim(preg_replace('/(?<!^)[A-Z]/', ' $0', $className) ?? $className);
        $loopVar = $this->camelPlural($className);

        // Kolom listing & field form mengikuti daftar field make:crud (lihat
        // helper crudIndexColumns()/crudFormInputs()).
        [$headCells, $bodyCells] = $this->crudIndexColumns($parsedFields, 'blade', $singularLower);
        $formInputs = $this->crudFormInputs($parsedFields, 'blade', $singularLower);

        return match ($view) {
            'index' => <<<BLADE
{{-- {$title} listing — generated by make:crud (blade engine) --}}
<h1>{$title}</h1>
<p><a href="{{ base_url('{$resourceSegment}/create') }}">+ Add New</a></p>

<table border="1" cellpadding="6">
    <thead>
        <tr>
            <th>ID</th>
{$headCells}            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach (\${$loopVar} as \${$singularLower})
        <tr>
            <td>{{ \${$singularLower}->id }}</td>
{$bodyCells}            <td>
                <a href="{{ base_url('{$resourceSegment}/show/' . \${$singularLower}->id) }}">Show</a> |
                <a href="{{ base_url('{$resourceSegment}/edit/' . \${$singularLower}->id) }}">Edit</a> |
                <form action="{{ base_url('{$resourceSegment}/delete/' . \${$singularLower}->id) }}" method="post" style="display:inline" onsubmit="return confirm('Delete?')">
                    <button type="submit">Delete</button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

BLADE,
            'create' => <<<BLADE
{{-- Create {$title} — generated by make:crud (blade engine) --}}
<h1>New {$title}</h1>
@if (!empty(\$error))
    <pre>{{ print_r(\$error, true) }}</pre>
@endif

<form action="{{ base_url('{$resourceSegment}') }}" method="post">
    @include('{$resourceSegment}/_form')
    <button type="submit">Save</button>
</form>
<a href="{{ base_url('{$resourceSegment}') }}">Back to list</a>

BLADE,
            'edit' => <<<BLADE
{{-- Edit {$title} — generated by make:crud (blade engine) --}}
<h1>Edit {$title}</h1>
@if (!empty(\$error))
    <pre>{{ print_r(\$error, true) }}</pre>
@endif

<form action="{{ base_url('{$resourceSegment}/update/' . \${$singularLower}->id) }}" method="post">
    @include('{$resourceSegment}/_form')
    <button type="submit">Update</button>
</form>
<a href="{{ base_url('{$resourceSegment}') }}">Back to list</a>

BLADE,
            'show' => <<<BLADE
{{-- Show {$title} — generated by make:crud (blade engine) --}}
<h1>{$title} #{{ \${$singularLower}->id }}</h1>
<pre>{{ print_r(\${$singularLower}, true) }}</pre>
<a href="{{ base_url('{$resourceSegment}/edit/' . \${$singularLower}->id) }}">Edit</a> |
<a href="{{ base_url('{$resourceSegment}') }}">Back to list</a>

BLADE,
            '_form' => <<<BLADE
{{-- Shared form fields for {$title} — generated by make:crud (blade engine) --}}
{$formInputs}
BLADE,
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
