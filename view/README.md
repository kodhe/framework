# Kodhe View Component

[![Latest Version](https://img.shields.io/github/v/release/kodhe/view)](https://github.com/kodhe/view/releases)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://php.net)
[![PSR-4](https://img.shields.io/badge/PSR--4-compliant-purple.svg)](https://www.php-fig.org/psr/psr-4/)
[![PSR-12](https://img.shields.io/badge/PSR--12-compliant-purple.svg)](https://www.php-fig.org/psr/psr-12/)

A modular, high-performance view component for CodeIgniter 3 applications with support for multiple template engines (Blade, PHP, Twig), theme management, asset management, and variant detection.

## Features

- **Modular Architecture**: Clean separation of concerns with dedicated classes for each responsibility
- **PSR-4 Autoloading**: Fully compliant with PSR-4 autoloading standards
- **PSR-12 Coding Style**: All code follows PSR-12 coding standards
- **PHPUnit Ready**: Comprehensive test suite with unit and integration tests
- **High Performance**: Optimized rendering with caching support
- **100% Backward Compatible**: Full compatibility with CodeIgniter 3
- **Multiple Template Engines**: Support for Blade, PHP, and Twig
- **Theme System**: Advanced theme management with variants (mobile, tablet, etc.)
- **Asset Management**: CSS/JS management with grouping, priorities, and cache busting
- **Helper Functions**: Extensive helper functions for views and templates

## Installation

```bash
composer require kodhe/view
```

## Requirements

- PHP >= 8.1
- CodeIgniter 3.x
- eftec/bladeone ^4.19 (automatically installed)

## Quick Start

### Basic Usage

```php
// In your controller
$this->load->library('template');

// Render a view
$this->template->view('welcome', $data);

// Or using helper function
view('welcome', $data);
```

### Using Blade Templates

```blade
{{-- resources/views/welcome.blade.php --}}
@extends('layouts.main')

@section('content')
    <h1>Hello, {{ $name }}!</h1>
@endsection
```

### Asset Management

```php
// Add CSS
$this->assets->add_css('style.css', 'theme', ['media' => 'screen'], 10);

// Add JS
$this->assets->add_js('app.js', 'theme', [], 'footer', 10);

// In your layout
<head>
    {!! $this->assets->render_css() !!}
</head>
<body>
    <!-- Content -->
    {!! $this->assets->render_js('footer') !!}
</body>
```

### Theme System

```php
// Enable theme system
$this->template->enableTheme(true);

// Set active theme
$this->template->setTheme('modern');

// Get theme info
$themeInfo = $this->template->getThemeInfo();

// Get theme asset URL
$assetUrl = $this->template->themeAsset('css/style.css');
```

## Directory Structure

```
view/
├── src/
│   ├── AssetManager.php          # Asset management
│   ├── ThemeManager.php           # Theme handling
│   ├── VariantManager.php         # Device variant detection
│   ├── View.php                   # Main view class
│   ├── ViewFactory.php            # Factory for creating views
│   ├── ViewInterface.php          # View interface
│   ├── ViewLoader.php             # CI3 loader compatibility
│   ├── Config/                    # Configuration classes
│   ├── Contracts/                 # Interfaces
│   ├── Engine/                    # Template engines
│   │   ├── BladeEngine.php
│   │   ├── EngineFactory.php
│   │   ├── EngineInterface.php
│   │   ├── PhpEngine.php
│   │   └── TwigEngine.php
│   ├── Exceptions/                # Custom exceptions
│   └── helpers/                   # Helper functions
│       ├── template.php
│       └── template_asset.php
├── tests/
│   ├── bootstrap.php              # PHPUnit bootstrap
│   ├── Unit/                      # Unit tests
│   └── Integration/               # Integration tests
├── config/                        # Configuration files
├── composer.json
├── phpunit.xml
└── README.md
```

## Running Tests

```bash
# Install dependencies
composer install

# Run all tests
composer test

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/

# Check code style
composer phpcs

# Fix code style automatically
composer phpcbf

# Static analysis
composer phpstan
```

## Configuration

Create a `config/template.php` file in your application:

```php
<?php

return [
    // Default template engine
    'default' => 'blade',
    
    // Views path
    'views_path' => APPPATH . 'views/',
    
    // Cache path
    'cache_path' => STORAGEPATH . 'cache/blade/',
    
    // Theme settings
    'theme_enabled' => true,
    'theme_default' => 'default',
    'theme_locations' => [
        APPPATH . 'themes/',
        FCPATH . 'themes/'
    ],
    
    // Asset settings
    'assets' => [
        'assets_dir' => 'assets',
        'combine' => ENVIRONMENT === 'production',
        'minify' => ENVIRONMENT === 'production',
    ]
];
```

## Available Helper Functions

### View Rendering
- `view($view, $data = [], $return = false)` - Render a view
- `theme_view($view, $data = [], $return = false, $theme = null)` - Render with specific theme
- `template_exists($view)` - Check if view exists

### Asset Management
- `add_css($file, $group, $attributes, $priority)` - Add CSS file
- `add_js($file, $group, $attributes, $position, $priority)` - Add JS file
- `add_inline_css($css, $priority)` - Add inline CSS
- `add_inline_js($js, $position, $priority)` - Add inline JS
- `theme_asset($path)` - Get theme asset URL
- `theme_css($file)` - Get theme CSS URL
- `theme_js($file)` - Get theme JS URL
- `theme_img($file)` - Get theme image URL

### Theme Management
- `set_theme($theme)` - Set active theme
- `get_active_theme()` - Get current theme
- `get_available_themes()` - Get all available themes
- `set_theme_preview($theme)` - Preview a theme
- `clear_theme_preview()` - Clear theme preview

### Include Helpers
- `include_partial($partial, $data)` - Render partial view
- `include_widget($widget, $params)` - Render widget
- `include_section($section, $data)` - Render section
- `include_element($element, $data)` - Render element
- `include_block($block, $data)` - Render block
- `include_module($module, $data)` - Render module
- `include_region($region, $data)` - Render region

## License

This package is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch
3. Write tests for new functionality
4. Ensure all tests pass
5. Follow PSR-12 coding standards
6. Submit a pull request

## Support

For issues and feature requests, please use the GitHub issue tracker.

---

**Note**: This component is designed to be 100% backward compatible with CodeIgniter 3 while providing modern features and architecture.
