# Kodhe FTP Library

FTP Library yang telah direfactor untuk CodeIgniter 3 dengan arsitektur modular PSR-4.

## Fitur

- ✅ **PSR-4 Autoloading** - Namespace `Kodhe\Ftp\`
- ✅ **PSR-12 Coding Standards** - Clean code style
- ✅ **Design Patterns**:
  - Facade Pattern (class Ftp utama)
  - Adapter Pattern (ConnectionInterface)
  - Strategy Pattern (mode transfer ascii/binary)
- ✅ **Multiple Connections** - Support FTP dan FTPS (SSL)
- ✅ **Testable** - Connection bisa di-mock untuk unit testing
- ✅ **100% Backward Compatible** - API CI3 tetap berfungsi

## Struktur Folder

```
ftp/
├── src/
│   ├── Ftp.php                       # Main class (Facade)
│   ├── Contracts/
│   │   ├── FtpInterface.php          # Interface utama
│   │   └── ConnectionInterface.php   # Interface koneksi
│   ├── Connection/
│   │   ├── FtpConnection.php         # FTP biasa
│   │   └── FtpSslConnection.php      # FTP over SSL
│   ├── Operations/
│   │   ├── FileOperations.php        # Upload, download, delete, rename, chmod
│   │   └── DirectoryOperations.php   # Mkdir, list_files, delete_dir, changedir
│   └── Validation/
│       └── ModeResolver.php          # Auto-detect mode ascii/binary
├── tests/
├── composer.json
└── README.md
```

## Instalasi

### Via Composer

```bash
composer require kodhe/ftp
```

### Manual

Copy folder `src/` ke project Anda dan setup autoloading:

```php
// Di application/config/config.php atau bootstrap
spl_autoload_register(function ($class) {
    $prefix = 'Kodhe\\Ftp\\';
    $base_dir = APPPATH . 'libraries/Ftp/src/';
    
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
class BackupController extends CI_Controller {
    
    public function upload_backup() {
        $this->load->library('ftp');
        
        $config = [
            'hostname' => 'ftp.example.com',
            'username' => 'user',
            'password' => 'pass',
            'port'     => 21,
            'passive'  => true,
            'debug'    => true,
        ];
        
        if ($this->ftp->connect($config)) {
            // Upload file
            $this->ftp->upload('/local/path/backup.zip', '/remote/backup.zip', 'binary');
            
            // List files
            $files = $this->ftp->list_files('/remote/');
            
            // Delete file
            $this->ftp->delete_file('/remote/old_backup.zip');
            
            $this->ftp->close();
        }
    }
}
```

### Modern PSR-4 Style

```php
use Kodhe\Ftp\Ftp;
use Kodhe\Ftp\Connection\FtpConnection;

// Dengan constructor
$ftp = new Ftp([
    'hostname' => 'ftp.example.com',
    'username' => 'user',
    'password' => 'pass',
    'ssl'      => false,
    'passive'  => true,
]);

// Atau manual connect
$ftp = new Ftp();
$ftp->connect([
    'hostname' => 'ftp.example.com',
    'username' => 'user', 
    'password' => 'pass',
]);

// Upload dengan auto-detect mode
$ftp->upload('/local/file.txt', '/remote/file.txt'); // ASCII
$ftp->upload('/local/image.jpg', '/remote/image.jpg'); // Binary

// Download
$ftp->download('/remote/file.txt', '/local/file.txt');

// Rename/Move
$ftp->rename('/remote/old.txt', '/remote/new.txt');
$ftp->move('/remote/file.txt', '/remote/folder/file.txt');

// Create directory
$ftp->mkdir('/remote/new_folder', 0755);

// Change directory
$ftp->changedir('/remote/folder');

// List files
$files = $ftp->list_files('/remote');

// Delete
$ftp->delete_file('/remote/file.txt');
$ftp->delete_dir('/remote/folder');

// Chmod
$ftp->chmod('/remote/file.txt', 0644);

// Close connection
$ftp->close();
```

### FTP over SSL (FTPS)

```php
use Kodhe\Ftp\Ftp;

$ftp = new Ftp([
    'hostname' => 'secure.example.com',
    'username' => 'user',
    'password' => 'pass',
    'ssl'      => true,  // Enable SSL
    'port'     => 990,   // Port FTPS
    'passive'  => true,
]);
```

## Methods

| Method | Deskripsi |
|--------|-----------|
| `connect($config)` | Connect ke server FTP |
| `upload($local, $remote, $mode, $perm)` | Upload file |
| `download($remote, $local, $mode)` | Download file |
| `rename($old, $new, $move)` | Rename file |
| `move($old, $new)` | Move file (alias rename) |
| `delete_file($path)` | Delete file |
| `delete_dir($path)` | Delete direktori (rekursif) |
| `mkdir($path, $perm)` | Buat direktori |
| `list_files($path)` | List file dalam direktori |
| `changedir($path, $suppress)` | Change directory |
| `chmod($path, $perm)` | Change permissions |
| `close()` | Tutup koneksi |

## Mode Transfer

Library otomatis mendeteksi mode transfer berdasarkan ekstensi file:

**ASCII Mode** (otomatis untuk):
- `.txt`, `.text`, `.php`, `.html`, `.htm`, `.css`, `.js`, `.json`
- `.xml`, `.csv`, `.log`, `.sql`, `.md`, `.yaml`, `.yml`

**Binary Mode** (default untuk lainnya):
- `.jpg`, `.png`, `.gif`, `.zip`, `.tar`, `.gz`
- Dan semua ekstensi lainnya

## Testing

```bash
cd ftp
composer install
composer test
```

## License

MIT License
