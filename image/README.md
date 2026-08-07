# Kodhe Image Library

Refactored CodeIgniter 3 Image Library with PSR-4/PSR-12 compliance, maintaining full backward compatibility.

## Features

- **Modular Architecture**: Separated into Contracts, Drivers, Operations, Factory, Support, and ValueObjects
- **Design Patterns**: Strategy, Factory, Command, Value Object, Dependency Injection
- **Performance Optimizations**: Metadata caching, lazy loading, resource reuse
- **PSR-4 Autoloading**: Fully compliant with PSR-4 namespace standards
- **PSR-12 Coding Style**: Follows modern PHP coding standards
- **PHPUnit Ready**: Testable architecture with dependency injection

## Structure

```
src/
├── ImageLib.php              # Main CI3-compatible class
├── Contracts/
│   └── ImageDriverInterface.php
├── Drivers/
│   ├── GdDriver.php          # GD library driver
│   └── ImagickDriver.php     # ImageMagick driver (TODO)
├── Factory/
│   └── DriverFactory.php
├── Operations/               # Image operations (TODO)
├── Support/
│   └── ImageMetadataCache.php
└── ValueObjects/
    ├── ImageDimensions.php
    └── ImageInfo.php
```

## Installation

```bash
composer require kodhe/image
```

## Usage

### Basic Usage (CI3 Compatible)

```php
use Kodhe\Image\ImageLib;

$config = [
    'image_library' => 'gd2',
    'source_image'  => '/path/to/image.jpg',
    'new_image'     => '/path/to/thumb.jpg',
    'width'         => 200,
    'height'        => 200,
];

$image = new ImageLib($config);

// Resize
if (!$image->resize()) {
    echo $image->display_errors();
}

// Crop
$image->clear();
$image->initialize([
    'source_image' => '/path/to/image.jpg',
    'x_axis' => 50,
    'y_axis' => 50,
    'width' => 100,
    'height' => 100,
]);
$image->crop();

// Rotate
$image->clear();
$image->initialize([
    'source_image'   => '/path/to/image.jpg',
    'rotation_angle' => 90,
]);
$image->rotate();

// Watermark
$image->clear();
$image->initialize([
    'source_image'    => '/path/to/image.jpg',
    'wm_type'         => 'text',
    'wm_text'         => 'Copyright 2024',
    'wm_font_size'    => 16,
    'wm_vrt_alignment'=> 'B',
    'wm_hor_alignment'=> 'C',
]);
$image->watermark();

// Flip
$image->clear();
$image->initialize([
    'source_image' => '/path/to/image.jpg',
]);
$image->flip('horizontal'); // or 'vertical'
```

## API Methods

All original CI3 Image_lib methods are preserved:

- `initialize(array $config)` - Initialize with configuration
- `resize()` - Resize image
- `crop()` - Crop image  
- `rotate()` - Rotate image
- `watermark()` - Add watermark
- `flip(string $direction)` - Flip/mirror image
- `clear()` - Clear resources and reset state
- `display_errors(string $open, string $close)` - Get formatted error messages

## Design Patterns Used

### Strategy Pattern
Different image processing drivers (GD, ImageMagick) implement the same interface.

### Factory Pattern
`DriverFactory` creates appropriate driver instances based on configuration.

### Value Object Pattern
Immutable value objects for image dimensions and metadata.

### Dependency Injection
Drivers can be injected for easier testing.

## Performance Features

- **Metadata Caching**: Image properties cached to avoid repeated `getimagesize()` calls
- **Lazy Loading**: Drivers loaded only when needed
- **Resource Reuse**: GD/Imagick resources reused where possible

## Testing

```bash
composer install
vendor/bin/phpunit tests/
```

## License

MIT License
