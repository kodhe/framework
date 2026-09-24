# Kodhe Agent Library

A modern, modular, PSR-4 compatible User Agent detection library with full backward compatibility for CodeIgniter 3.

## Features

- **PSR-4 Autoloading**: Modern namespace-based autoloading
- **PSR-12 Coding Style**: Follows PHP-FIG coding standards
- **Modular Architecture**: Separated into drivers, parsers, and collections
- **Dependency Injection**: Easy to test and extend
- **CodeIgniter 3 Compatible**: 100% backward compatible with CI3 API
- **Unit Testable**: Built with testing in mind

## Installation

### Via Composer

```bash
composer require kodhe/agent
```

### Manual Installation

1. Clone or download the repository
2. Copy the `src` folder to your project
3. Include the autoloader:

```php
require_once 'path/to/agent/src/compat.php';
```

## Usage

### CodeIgniter 3 Style (Backward Compatible)

```php
// Load the library
$this->load->library('agent');

// Browser detection
echo $this->agent->browser();      // e.g., "Chrome"
echo $this->agent->version();       // e.g., "120.0.0.0"
echo $this->agent->platform();      // e.g., "Windows 10"

// Device detection
echo $this->agent->mobile();        // e.g., "iPhone"
echo $this->agent->is_mobile();     // true/false
echo $this->agent->is_robot();      // true/false

// Referrer detection
echo $this->agent->referrer();
echo $this->agent->is_referral();

// Language and charset
echo $this->agent->accept_lang('en');
echo $this->agent->accept_charset('utf-8');
```

### Modern PSR-4 Style

```php
use Kodhe\Agent\Agent;

$agent = new Agent();

// Browser detection
echo $agent->browser();
echo $agent->version();

// Device detection  
echo $agent->isMobile();
echo $agent->isDesktop();
echo $agent->isRobot();

// Advanced usage with drivers
$browserDriver = $agent->getBrowserDriver();
echo $browserDriver->getBrowser();
echo $browserDriver->getVersion();
```

### Custom User Agent Parsing

```php
$agent = new Agent();
$agent->parse('Mozilla/5.0 (Custom Agent String)');

echo $agent->browser();
echo $agent->platform();
```

## Architecture

```
src/
├── Agent.php                    # Main Agent class
├── Contracts/
│   ├── AgentInterface.php       # Main interface
│   └── AgentDriverInterface.php # Driver interface
├── Drivers/
│   ├── BrowserDriver.php        # Browser detection
│   ├── DeviceDriver.php         # Mobile device detection
│   ├── OsDriver.php             # OS/Platform detection
│   └── RobotDriver.php          # Robot/Crawler detection
├── Parsers/
│   └── UserAgentParser.php      # User agent string parser
├── Collections/
│   ├── BrowserCollection.php    # Browser data
│   ├── DeviceCollection.php     # Mobile device data
│   ├── OsCollection.php         # OS/Platform data
│   └── RobotCollection.php      # Robot/Crawler data
├── Traits/                      # Optional traits
└── compat.php                   # CI3 compatibility layer
```

## Design Patterns Used

- **Strategy Pattern**: Each driver implements detection strategy
- **Factory Pattern**: Drivers are created through dependency injection
- **Dependency Injection**: All dependencies injected via constructor
- **Interface Segregation**: Separate interfaces for different responsibilities
- **Single Responsibility**: Each class has one job

## Configuration

Create `application/config/agent.php`:

```php
<?php
$config['agent'] = [
    'cache_detection' => true,
    'cache_ttl' => 3600,
    'detect_robots' => true,
    'detect_mobile' => true,
];
```

Load configuration:

```php
$agent = new Agent();
$agent->loadConfig();
```

## Running Tests

```bash
composer install
vendor/bin/phpunit tests/
```

## API Reference

### Main Methods (CI3 Compatible)

| Method | Description | Returns |
|--------|-------------|---------|
| `browser()` | Get browser name | string |
| `version()` | Get browser version | string |
| `platform()` | Get OS/Platform | string |
| `mobile()` | Get mobile device | string |
| `robot()` | Get robot name | string |
| `is_browser($key)` | Check if browser | bool |
| `is_mobile($key)` | Check if mobile | bool |
| `is_robot($key)` | Check if robot | bool |
| `is_referral()` | Check if referral | bool |
| `agent_string()` | Get full UA string | string |
| `languages()` | Get accepted languages | array |
| `charsets()` | Get accepted charsets | array |
| `accept_lang($lang)` | Check language | bool |
| `accept_charset($charset)` | Check charset | bool |
| `referrer()` | Get referrer URL | string |
| `parse($string)` | Parse custom UA | void |

### Modern Methods (PSR-4)

| Method | Description | Returns |
|--------|-------------|---------|
| `isBrowser($key)` | Check if browser | bool |
| `isMobile($key)` | Check if mobile | bool |
| `isRobot($key)` | Check if robot | bool |
| `isDesktop()` | Check if desktop | bool |
| `isReferral()` | Check if referral | bool |
| `agentString()` | Get full UA string | string |
| `acceptLang($lang)` | Check language | bool |
| `acceptCharset($charset)` | Check charset | bool |

### Driver Access Methods

```php
$agent->getBrowserDriver()  // BrowserDriver instance
$agent->getDeviceDriver()   // DeviceDriver instance
$agent->getOsDriver()       // OsDriver instance
$agent->getRobotDriver()    // RobotDriver instance
$agent->getParser()         // UserAgentParser instance
```

## Extending the Library

### Custom Browser Collection

```php
use Kodhe\Agent\Collections\BrowserCollection;

$collection = new BrowserCollection();
$collection->add('MyBrowser', 'My Custom Browser');

$agent = new Agent();
$agent->getBrowserDriver()->setCollection($collection);
```

### Custom Driver

```php
use Kodhe\Agent\Contracts\AgentDriverInterface;
use Kodhe\Agent\Parsers\UserAgentParser;

class CustomDriver implements AgentDriverInterface {
    private $parser;
    
    public function __construct(UserAgentParser $parser) {
        $this->parser = $parser;
        $this->detect();
    }
    
    public function detect(): void {
        // Custom detection logic
    }
    
    public function getValue(): string {
        return 'custom_value';
    }
    
    public function isMatch(?string $key = null): bool {
        return true;
    }
}
```

## Requirements

- PHP 8.1+
- For CodeIgniter 3: CodeIgniter 3.x

## License

MIT License - see LICENSE file for details.

## Contributing

1. Fork the repository
2. Create your feature branch
3. Run tests: `vendor/bin/phpunit`
4. Submit a pull request

## Changelog

### Version 2.0.0
- Complete refactor to PSR-4 structure
- Added modular drivers architecture
- Full CodeIgniter 3 backward compatibility
- Added comprehensive unit tests
- PSR-12 coding standards compliance

### Version 1.1.0
- Initial release
