# Kodhe Framework Console Component

Console component untuk Kodhe Framework menyediakan command-line interface (CLI) yang powerful untuk menjalankan berbagai tugas development dan maintenance.

## 📦 Instalasi

```bash
composer require kodhe/framework
```

## 🚀 Kegunaan Console dalam Projek

Console component digunakan untuk:

1. **Code Generation** - Membuat boilerplate code (commands, controllers, models, migrations, middleware)
2. **Task Automation** - Menjalankan scheduled tasks dan background jobs
3. **Database Management** - Migrasi, seeding, dan operasi database lainnya
4. **Cache Management** - Clear cache, warm cache
5. **Queue Management** - Process queue, monitor workers
6. **Application Maintenance** - Mode maintenance, clear config cache
7. **Testing** - Run tests dari command line
8. **Custom Commands** - Membuat command khusus untuk business logic

## 📁 Struktur Package

```
src/Console/
├── Console.php                 # Main console application manager
├── Command.php                 # Base command class
├── Input.php                   # Input handler
├── InputInterface.php          # Input interface
├── Output.php                  # Output handler
├── OutputInterface.php         # Output interface
├── Exceptions/
│   └── CommandNotFoundException.php
└── Commands/
    ├── HelpCommand.php         # Show help information
    ├── ListCommand.php         # List all commands
    ├── VersionCommand.php      # Show version info
    └── MakeCommand.php         # Generate boilerplate code
```

## 💻 Penggunaan Dasar

### Menjalankan Console

```bash
# Dari direktori project
php bin/console <command> [options] [arguments]

# Contoh
php bin/console help
php bin/console list
php bin/console version
```

### Built-in Commands

#### 1. Help Command
Menampilkan informasi bantuan untuk command tertentu atau semua command.

```bash
# Tampilkan semua command yang tersedia
php bin/console help

# Tampilkan bantuan untuk command spesifik
php bin/console help list
php bin/console help make
```

#### 2. List Command
Daftar semua command yang terdaftar.

```bash
# List dengan format tabel
php bin/console list

# List raw text
php bin/console list --raw

# Alias
php bin/console ls
```

#### 3. Version Command
Tampilkan versi framework dan informasi sistem.

```bash
# Versi lengkap
php bin/console version

# Hanya nomor versi
php bin/console version --short

# Aliases
php bin/console -v
php bin/console --version
```

#### 4. Make Command
Generate boilerplate code untuk berbagai komponen.

```bash
# Buat command baru
php bin/console make:command MyCommand

# Buat controller
php bin/console make:controller UserController

# Buat model
php bin/console make:model User

# Buat migration
php bin/console make:migration create_users_table

# Buat middleware
php bin/console make:middleware AuthMiddleware

# Force overwrite jika file sudah ada
php bin/console make:command MyCommand --force

# Aliases
php bin/console g:command MyCommand
php bin/console generate:controller HomeController
```

## 🛠️ Membuat Custom Command

### Langkah 1: Extend Command Class

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Kodhe\Framework\Console\Command;
use Kodhe\Framework\Console\Input;
use Kodhe\Framework\Console\Output;

class GreetCommand extends Command
{
    protected string $name = 'app:greet';
    protected string $description = 'Greet someone by name';
    protected array $usage = [
        'app:greet {name}',
        'app:greet John --formal',
    ];
    protected array $arguments = [
        'name' => 'Name of the person to greet',
    ];
    protected array $options = [
        'formal' => 'Use formal greeting',
    ];
    protected array $aliases = ['greet'];

    public function handle(): int
    {
        $name = $this->argument('name');
        $formal = $this->option('formal');
        
        if ($formal) {
            $this->success("Good day, {$name}!");
        } else {
            $this->info("Hey {$name}, what's up?");
        }
        
        return 0;
    }
}
```

### Langkah 2: Daftarkan Command

```php
use Kodhe\Framework\Console\Console;

$console = Console::getInstance();
$console->addCommand(new \App\Console\Commands\GreetCommand());

// Atau tambahkan multiple commands
$console->addCommands([
    new \App\Console\Commands\GreetCommand(),
    new \App\Console\Commands\SendEmailCommand(),
]);
```

### Langkah 3: Jalankan Command

```bash
php bin/console app:greet John
php bin/console app:greet Jane --formal
php bin/console greet Bob
```

## 📖 API Reference

### Console Class

| Method | Description |
|--------|-------------|
| `getInstance()` | Get singleton instance |
| `addCommand(Command $command)` | Register a command |
| `addCommands(array $commands)` | Register multiple commands |
| `hasCommand(string $name)` | Check if command exists |
| `getCommand(string $name)` | Get command by name |
| `getCommands()` | Get all registered commands |
| `run(?Input $input, ?Output $output)` | Run console application |
| `runCommand(string $name, array $args, Output $output)` | Run specific command |
| `setName(string $name)` | Set console name |
| `setVersion(string $version)` | Set console version |

### Command Class (Base)

| Method | Description |
|--------|-------------|
| `getName()` | Get command name |
| `getDescription()` | Get command description |
| `handle()` | Execute command (abstract) |
| `argument($name, $default)` | Get argument value |
| `option($name, $default)` | Get option value |
| `hasArgument($name)` | Check if argument exists |
| `hasOption($name)` | Check if option exists |
| `write($message, $newline)` | Write output |
| `writeln($message)` | Write line |
| `info($message)` | Write info message |
| `success($message)` | Write success message |
| `warning($message)` | Write warning message |
| `error($message)` | Write error message |
| `debug($message)` | Write debug message |
| `table($headers, $rows)` | Display table |
| `ask($question, $default)` | Ask user input |
| `confirm($question, $default)` | Ask confirmation |
| `choice($question, $options, $default)` | Choose from options |
| `call($command, $args)` | Call another command |

### Input Class

| Method | Description |
|--------|-------------|
| `getArguments()` | Get all arguments |
| `getArgument($name, $default)` | Get specific argument |
| `hasArgument($name)` | Check argument exists |
| `setArgument($name, $value)` | Set argument value |
| `getOptions()` | Get all options |
| `getOption($name, $default)` | Get specific option |
| `hasOption($name)` | Check option exists |
| `setOption($name, $value)` | Set option value |
| `getFirstArgument()` | Get command name |
| `getTokens()` | Get raw tokens |

### Output Class

| Method | Description |
|--------|-------------|
| `write($message, $newline)` | Write without newline |
| `writeln($message)` | Write with newline |
| `setVerbosity($level)` | Set verbosity level |
| `getVerbosity()` | Get verbosity level |
| `isQuiet()` | Check quiet mode |
| `isVerbose()` | Check verbose mode |
| `isVeryVerbose()` | Check very verbose mode |
| `isDebug()` | Check debug mode |
| `info($message)` | Write info |
| `success($message)` | Write success |
| `warning($message)` | Write warning |
| `error($message)` | Write error |
| `debug($message)` | Write debug |
| `table($headers, $rows)` | Display table |
| `ask($question, $default)` | Ask question |
| `confirm($question, $default)` | Ask confirmation |
| `choice($question, $options)` | Choose option |
| `progress($current, $total, $msg)` | Show progress |

## 🧪 Testing

```bash
# Run all console tests
vendor/bin/phpunit tests/Console/

# Run specific test
vendor/bin/phpunit tests/Console/InputTest.php
vendor/bin/phpunit tests/Console/OutputTest.php
vendor/bin/phpunit tests/Console/ConsoleTest.php
```

## 📝 Best Practices

1. **Naming Convention**: Gunakan prefix namespace untuk command (e.g., `app:`, `db:`, `cache:`)
2. **Single Responsibility**: Satu command untuk satu tugas spesifik
3. **Error Handling**: Selalu handle exceptions di dalam `handle()` method
4. **Exit Codes**: Return 0 untuk success, 1 untuk error
5. **User Feedback**: Berikan feedback yang jelas kepada user
6. **Documentation**: Isi property `$description`, `$usage`, `$arguments`, `$options` dengan lengkap

## 🎯 Contoh Command Lengkap

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Kodhe\Framework\Console\Command;

class SendNewsletterCommand extends Command
{
    protected string $name = 'newsletter:send';
    protected string $description = 'Send newsletter to all subscribers';
    protected array $options = [
        'limit' => 'Maximum number of emails to send',
        'test' => 'Send to test email only',
    ];

    public function handle(): int
    {
        $limit = (int) $this->option('limit', 100);
        $testMode = $this->option('test');
        
        $this->info('Starting newsletter delivery...');
        
        if ($testMode) {
            $this->warning('Running in test mode');
        }
        
        $subscribers = $this->getSubscribers($limit);
        $total = count($subscribers);
        
        foreach ($subscribers as $index => $subscriber) {
            $this->progress($index + 1, $total, "Sending to {$subscriber['email']}");
            
            // Send email logic here
            
            usleep(100000); // Rate limiting
        }
        
        $this->success("Newsletter sent to {$total} subscribers!");
        
        return 0;
    }
    
    private function getSubscribers(int $limit): array
    {
        // Implementation here
        return [];
    }
}
```

## 📄 License

MIT License - see LICENSE file for details.

## 🤝 Contributing

Contributions are welcome! Please read our contributing guidelines before submitting PRs.

## 📞 Support

- GitHub Issues: https://github.com/karyakode/kodhe/issues
- Documentation: https://kodhe.dev/docs
