# Kodhe Zip Library

Library ZIP archive modern untuk CodeIgniter 3 dengan arsitektur modular PSR-4/PSR-12, mendukung pembuatan dan ekstraksi file ZIP.

## Fitur

- **PSR-4 Autoloading** - Namespace `Kodhe\Framework\Zip\`
- **PSR-12 Coding Standards** - Clean code style
- **Design Patterns**:
  - Strategy Pattern (compression methods)
  - Factory Pattern (reader/writer creation)
  - Value Object Pattern (ZipEntry, ZipArchive)
  - Dependency Injection (easy testing)
- **Multiple Compression Methods** - Deflate, Store, BZIP2
- **Streaming Support** - Read/write large files without loading into memory
- **100% Backward Compatible** - API CI3 tetap berfungsi

## Instalasi

### Via Composer

```bash
composer require kodhe/zip
```

### Manual

Copy folder `src/` ke project Anda dan setup autoloading di `application/config/config.php`:

```php
spl_autoload_register(function ($class) {
    $prefix = 'Kodhe\\Framework\\Zip\\';
    $base_dir = APPPATH . 'libraries/Zip/src/';

    if (strpos($class, $prefix) === 0) {
        $relative_class = substr($class, strlen($prefix));
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});
```

## Penggunaan

### CodeIgniter 3 Style (Backward Compatible)

```php
class DownloadController extends CI_Controller {

    public function download_backup() {
        $this->load->library('zip');

        // Add files to zip
        $this->zip->read_dir('./uploads/documents/');
        $this->zip->read_file('./config/database.php');
        
        // Add data from database
        $data = $this->db->get('users')->result_array();
        $this->zip->add_data('users_export.json', json_encode($data));

        // Download the zip file
        $this->zip->download('backup_' . date('Y-m-d') . '.zip');
    }
}
```

### Modern PSR-4 Style

```php
use Kodhe\Framework\Zip\Zip;
use Kodhe\Framework\Zip\Writers\FileWriter;

// Create new zip archive
$zip = new Zip();

// Add directory recursively
$zip->readDir('./uploads/documents/', true); // preserve path

// Add single file
$zip->readFile('./config/database.php');

// Add data from string
$zip->addData('readme.txt', 'This is a text file content');

// Add file with custom name in archive
$zip->addFile('./reports/report.pdf', 'documents/annual_report.pdf');

// Download to browser
$zip->download('archive.zip');

// Or save to server
$zip->archive('./backups/archive.zip');

// Clear for reuse
$zip->clear();
```

### Extract ZIP Archive

```php
use Kodhe\Framework\Zip\Readers\ZipReader;

$reader = new ZipReader('./archive.zip');

// Extract all files
$reader->extractTo('./extracted/');

// Extract specific file
$content = $reader->getFileContent('documents/report.pdf');

// List all entries
$entries = $reader->listEntries();
foreach ($entries as $entry) {
    echo "File: " . $entry->getName() . " (Size: " . $entry->getSize() . " bytes)\n";
}

// Check if entry exists
if ($reader->hasEntry('config/settings.json')) {
    $settings = json_decode($reader->getFileContent('config/settings.json'), true);
}
```

### Advanced Usage

```php
use Kodhe\Framework\Zip\Zip;
use Kodhe\Framework\Zip\Compression\DeflateCompression;

$zip = new Zip();

// Set compression method
$zip->setCompression(new DeflateCompression(9)); // Level 1-9

// Add with password protection (if supported)
$zip->addData('secret.txt', 'Confidential data', ['password' => 'secret123']);

// Set archive comment
$zip->setComment('Created on ' . date('Y-m-d H:i:s'));

// Get archive info before download
$info = $zip->getArchiveInfo();
echo "Total files: " . $info['file_count'] . "\n";
echo "Total size: " . $info['total_size'] . " bytes\n";
```

## Configuration Options

| Option | Default | Description |
|--------|---------|-------------|
| `compression_level` | `6` | Compression level (1-9, 0 = store) |
| `preserve_path` | `true` | Preserve directory structure |
| `include_hidden` | `false` | Include hidden files (.dotfiles) |
| `max_size` | `0` | Max archive size (0 = unlimited) |
| `comment` | `''` | Archive comment |

## Methods

### Main Methods

| Method | Description |
|--------|-------------|
| `readDir($path, $preserve_filepath = false)` | Add directory recursively |
| `readFile($path, $archive_path = null)` | Add single file |
| `addData($filename, $data)` | Add data from string |
| `addFile($source, $archive_name)` | Add file with custom name |
| `download($filename)` | Download zip to browser |
| `archive($filepath)` | Save zip to server |
| `clear()` | Clear zip archive |
| `getZip()` | Get raw zip data |
| `setCompression($compression)` | Set compression method |
| `setComment($comment)` | Set archive comment |
| `getArchiveInfo()` | Get archive information |

### Data Array Keys (getArchiveInfo)

```php
[
    'file_count'   => 10,
    'total_size'   => 102400, // bytes
    'compressed'   => 51200,  // bytes
    'comment'      => 'Archive comment',
    'files'        => [
        ['name' => 'file1.txt', 'size' => 1024, 'compressed_size' => 512],
        // ...
    ]
]
```

## Struktur Package

```
zip/
├── src/
│   ├── Zip.php                       # Main class
│   ├── Contracts/
│   │   ├── ZipInterface.php          # Zip contract
│   │   ├── ReaderInterface.php       # Reader contract
│   │   └── WriterInterface.php       # Writer contract
│   ├── Readers/
│   │   └── ZipReader.php             # Read/extract ZIP files
│   ├── Writers/
│   │   └── ZipWriter.php             # Create ZIP archives
│   ├── Compression/
│   │   ├── CompressionInterface.php  # Compression contract
│   │   ├── DeflateCompression.php    # Deflate method
│   │   └── StoreCompression.php      # Store (no compression)
│   ├── ValueObjects/
│   │   ├── ZipEntry.php              # ZIP entry VO
│   │   └── ZipArchive.php            # Archive info VO
│   ├── Factory/
│   │   └── CompressionFactory.php    # Compression factory
│   └── Support/
│       └── Crc32Calculator.php       # CRC32 calculation
├── tests/
├── composer.json
└── README.md
```

## Testing

```bash
cd zip
composer install
vendor/bin/phpunit tests/
```

## License

MIT License - lihat file LICENSE untuk detail lengkap.
