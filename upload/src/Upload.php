<?php

declare(strict_types=1);

namespace Kodhe\Upload;

use Kodhe\Upload\Contracts\UploadInterface;
use Kodhe\Upload\Contracts\StorageInterface;
use Kodhe\Upload\ValueObjects\UploadedFile;
use Kodhe\Upload\Validators\FileValidator;
use Kodhe\Upload\Storage\LocalStorage;
use Kodhe\Upload\Support\MimeCache;
use Kodhe\Upload\Factory\FilenameStrategyFactory;
use ReflectionClass;

/**
 * File Uploading Class - Refactored Modular Version
 * 
 * Maintains 100% CodeIgniter 3 API compatibility while using
 * modern design patterns (Strategy, Factory, Validator, DI)
 * 
 * @package Kodhe\Upload
 */
class Upload implements UploadInterface
{
    public $max_size = 0;
    public $max_width = 0;
    public $max_height = 0;
    public $min_width = 0;
    public $min_height = 0;
    public $max_filename = 0;
    public $max_filename_increment = 100;
    public $allowed_types = '';
    public $file_temp = '';
    public $file_name = '';
    public $orig_name = '';
    public $file_type = '';
    public $file_size = null;
    public $file_ext = '';
    public $file_ext_tolower = false;
    public $upload_path = '';
    public $overwrite = false;
    public $encrypt_name = false;
    public $is_image = false;
    public $image_width = null;
    public $image_height = null;
    public $image_type = '';
    public $image_size_str = '';
    public $error_msg = [];
    public $remove_spaces = true;
    public $detect_mime = true;
    public $xss_clean = false;
    public $mod_mime_fix = true;
    public $temp_prefix = 'temp_file_';
    public $client_name = '';

    protected $_file_name_override = '';
    protected $_mimes = [];
    protected $_CI;
    protected $storage;
    protected $mimeCache;
    protected $validator = null;
    protected $uploadedFile = null;

    public function __construct($config = [])
    {
        empty($config) OR $this->initialize($config, false);
        $this->_mimes =& $this->getMimes();
        $this->_CI = $this->getCI();
        $this->storage = new LocalStorage();
        $this->mimeCache = new MimeCache();
        $this->logMessage('info', 'Upload Class Initialized');
    }

    public function initialize(array $config = [], bool $reset = true): self
    {
        $reflection = new ReflectionClass($this);
        if ($reset === true) {
            $defaults = $reflection->getDefaultProperties();
            foreach (array_keys($defaults) as $key) {
                if ($key[0] === '_') continue;
                if (isset($config[$key])) {
                    if ($reflection->hasMethod('set_' . $key)) {
                        $this->{'set_' . $key}($config[$key]);
                    } else {
                        $this->$key = $config[$key];
                    }
                } else {
                    $this->$key = $defaults[$key];
                }
            }
        } else {
            foreach ($config as $key => &$value) {
                if ($key[0] !== '_' && $reflection->hasProperty($key)) {
                    if ($reflection->hasMethod('set_' . $key)) {
                        $this->{'set_' . $key}($value);
                    } else {
                        $this->$key = $value;
                    }
                }
            }
        }
        $this->_file_name_override = $this->file_name;
        $this->validator = null;
        return $this;
    }

    public function do_upload($field = 'userfile'): bool
    {
        $_file = $this->getFileFromField($field);
        if ($_file === null) {
            $this->setError('upload_no_file_selected', 'debug');
            return false;
        }

        $this->uploadedFile = UploadedFile::fromArray($_file);
        if (!$this->uploadedFile->hasNoError()) {
            $this->handleUploadError($this->uploadedFile->getError());
            return false;
        }

        if (!$this->validate_upload_path()) return false;

        $this->file_temp = $this->uploadedFile->getTmpName();
        $this->file_size = $this->uploadedFile->getSize();

        if ($this->detect_mime !== false) $this->detectMimeType();

        $this->file_type = preg_replace('/^(.+?);.*$/', '\\1', $this->file_type);
        $this->file_type = strtolower(trim(stripslashes($this->file_type), '"'));
        $this->file_name = $this->_prep_filename($this->uploadedFile->getOriginalName());
        $this->file_ext = $this->get_extension($this->file_name);
        $this->client_name = $this->file_name;

        if (!$this->is_allowed_filetype()) {
            $this->setError('upload_invalid_filetype', 'debug');
            return false;
        }

        if ($this->_file_name_override !== '') {
            $this->file_name = $this->_prep_filename($this->_file_name_override);
            if (strpos($this->_file_name_override, '.') === false) {
                $this->file_name .= $this->file_ext;
            } else {
                $this->file_ext = $this->get_extension($this->_file_name_override);
            }
            if (!$this->is_allowed_filetype(true)) {
                $this->setError('upload_invalid_filetype', 'debug');
                return false;
            }
        }

        if ($this->file_size > 0) $this->file_size = round($this->file_size / 1024, 2);

        if (!$this->is_allowed_filesize()) {
            $this->setError('upload_invalid_filesize', 'info');
            return false;
        }

        if (!$this->is_allowed_dimensions()) {
            $this->setError('upload_invalid_dimensions', 'info');
            return false;
        }

        if ($this->_CI && isset($this->_CI->security)) {
            $this->file_name = $this->_CI->security->sanitize_filename($this->file_name);
        }

        if ($this->max_filename > 0) {
            $this->file_name = $this->limit_filename_length($this->file_name, $this->max_filename);
        }

        if ($this->remove_spaces === true) {
            $this->file_name = preg_replace('/\s+/', '_', $this->file_name);
        }

        if ($this->file_ext_tolower && ($ext_length = strlen($this->file_ext))) {
            $this->file_name = substr($this->file_name, 0, -$ext_length) . $this->file_ext;
        }

        $this->orig_name = $this->file_name;
        $finalFilename = $this->set_filename($this->upload_path, $this->file_name);
        if ($finalFilename === false) return false;
        $this->file_name = $finalFilename;

        if ($this->xss_clean && $this->do_xss_clean() === false) {
            $this->setError('upload_unable_to_write_file', 'error');
            return false;
        }

        if (!$this->moveFileToDestination()) {
            $this->setError('upload_destination_error', 'error');
            return false;
        }

        $this->set_image_properties($this->upload_path . $this->file_name);
        return true;
    }

    protected function getFileFromField(string $field): ?array
    {
        if (isset($_FILES[$field])) return $_FILES[$field];
        if (($c = preg_match_all('/(?:^[^\[]+)|\[[^]]*\]/', $field, $matches)) > 1) {
            $_file = $_FILES;
            for ($i = 0; $i < $c; $i++) {
                $field = trim($matches[0][$i], '[]');
                if ($field === '' || !isset($_file[$field])) return null;
                $_file = $_file[$field];
            }
            return $_file;
        }
        return null;
    }

    protected function handleUploadError(int $errorCode): void
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => ['upload_file_exceeds_limit', 'info'],
            UPLOAD_ERR_FORM_SIZE => ['upload_file_exceeds_form_limit', 'info'],
            UPLOAD_ERR_PARTIAL => ['upload_file_partial', 'debug'],
            UPLOAD_ERR_NO_FILE => ['upload_no_file_selected', 'debug'],
            UPLOAD_ERR_NO_TMP_DIR => ['upload_no_temp_directory', 'error'],
            UPLOAD_ERR_CANT_WRITE => ['upload_unable_to_write_file', 'error'],
            UPLOAD_ERR_EXTENSION => ['upload_stopped_by_extension', 'debug'],
        ];
        $error = $errors[$errorCode] ?? ['upload_no_file_selected', 'debug'];
        $this->setError($error[0], $error[1]);
    }

    protected function detectMimeType(): void
    {
        $this->file_type = MimeCache::detect($this->uploadedFile->getTmpName());
    }

    protected function moveFileToDestination(): bool
    {
        return $this->storage->store($this->file_temp, $this->upload_path . $this->file_name);
    }

    public function data($index = null)
    {
        $data = [
            'file_name' => $this->file_name,
            'file_type' => $this->file_type,
            'file_path' => $this->upload_path,
            'full_path' => $this->upload_path . $this->file_name,
            'raw_name' => substr($this->file_name, 0, -strlen($this->file_ext)),
            'orig_name' => $this->orig_name,
            'client_name' => $this->client_name,
            'file_ext' => $this->file_ext,
            'file_size' => $this->file_size,
            'is_image' => $this->is_image(),
            'image_width' => $this->image_width,
            'image_height' => $this->image_height,
            'image_type' => $this->image_type,
            'image_size_str' => $this->image_size_str,
        ];
        return !empty($index) ? ($data[$index] ?? null) : $data;
    }

    public function display_errors(string $open = '<p>', string $close = '</p>'): string
    {
        return count($this->error_msg) > 0 ? $open . implode($close . $open, $this->error_msg) . $close : '';
    }

    public function set_filename($path, $filename)
    {
        if ($this->encrypt_name === true) {
            $strategy = FilenameStrategyFactory::encrypt();
            return $strategy->generate(pathinfo($filename, PATHINFO_FILENAME), $this->file_ext, $path);
        }
        if ($this->overwrite !== true && $this->storage->exists($path . $filename)) {
            $strategy = new \Kodhe\Upload\Drivers\IncrementFilenameStrategy($this->max_filename_increment);
            $newFilename = $strategy->generate(pathinfo($filename, PATHINFO_FILENAME), $this->file_ext, $path);
            if ($newFilename === '') {
                $this->setError('upload_bad_filename', 'debug');
                return false;
            }
            return $newFilename;
        }
        return $filename;
    }

    public function setError($msg, string $logLevel = 'error'): self
    {
        if ($this->_CI && method_exists($this->_CI->lang, 'load')) $this->_CI->lang->load('upload');
        $msg = is_array($msg) ? $msg : [$msg];
        foreach ($msg as $val) {
            $translated = ($this->_CI && method_exists($this->_CI->lang, 'line')) ? $this->_CI->lang->line($val) : false;
            $this->error_msg[] = $translated === false ? $val : $translated;
            $this->logMessage($logLevel, $val);
        }
        return $this;
    }

    /**
     * Alias for do_upload() - PSR compliant method name
     * @param string $field
     * @return bool
     */
    public function doUpload(string $field = 'userfile'): bool
    {
        return $this->do_upload($field);
    }

    /**
     * Alias for display_errors() - PSR compliant method name
     * @param string $open
     * @param string $close
     * @return string
     */
    public function displayErrors(string $open = '<p>', string $close = '</p>'): string
    {
        return $this->display_errors($open, $close);
    }

    /**
     * Alias for set_filename() - PSR compliant method name
     * @param string $path
     * @param string $filename
     * @return string|false
     */
    public function setFilename(string $path, string $filename)
    {
        return $this->set_filename($path, $filename);
    }

    public function set_upload_path($path): self { $this->upload_path = rtrim($path, '/') . '/'; return $this; }
    public function set_max_filesize($n): self { $this->max_size = ($n < 0) ? 0 : (int) $n; return $this; }
    public function set_max_filename($n): self { $this->max_filename = ($n < 0) ? 0 : (int) $n; return $this; }
    public function set_max_width($n): self { $this->max_width = ($n < 0) ? 0 : (int) $n; return $this; }
    public function set_max_height($n): self { $this->max_height = ($n < 0) ? 0 : (int) $n; return $this; }
    public function set_min_width($n): self { $this->min_width = ($n < 0) ? 0 : (int) $n; return $this; }
    public function set_min_height($n): self { $this->min_height = ($n < 0) ? 0 : (int) $n; return $this; }
    
    public function set_allowed_types($types): self
    {
        $this->allowed_types = (is_array($types) || $types === '*') ? $types : explode('|', $types);
        return $this;
    }

    public function set_image_properties($path = ''): self
    {
        if ($this->is_image() && function_exists('getimagesize')) {
            if (($D = @getimagesize($path)) !== false) {
                $types = [1 => 'gif', 2 => 'jpeg', 3 => 'png'];
                $this->image_width = $D[0];
                $this->image_height = $D[1];
                $this->image_type = $types[$D[2]] ?? 'unknown';
                $this->image_size_str = $D[3];
            }
        }
        return $this;
    }

    public function set_xss_clean($flag = false): self { $this->xss_clean = ($flag === true); return $this; }

    public function is_image(): bool
    {
        $png_mimes = ['image/x-png'];
        $jpeg_mimes = ['image/jpg', 'image/jpe', 'image/jpeg', 'image/pjpeg'];
        if (in_array($this->file_type, $png_mimes)) $this->file_type = 'image/png';
        elseif (in_array($this->file_type, $jpeg_mimes)) $this->file_type = 'image/jpeg';
        return in_array($this->file_type, ['image/gif', 'image/jpeg', 'image/png'], true);
    }

    public function is_allowed_filetype($ignore_mime = false): bool
    {
        if ($this->allowed_types === '*') return true;
        if (empty($this->allowed_types) || !is_array($this->allowed_types)) {
            $this->setError('upload_no_file_types', 'debug');
            return false;
        }
        $ext = strtolower(ltrim($this->file_ext, '.'));
        if (!in_array($ext, $this->allowed_types, true)) return false;
        if (in_array($ext, ['gif', 'jpg', 'jpeg', 'jpe', 'png'], true) && @getimagesize($this->file_temp) === false) return false;
        if ($ignore_mime === true) return true;
        if (isset($this->_mimes[$ext])) {
            $allowedMimes = $this->_mimes[$ext];
            return is_array($allowedMimes) ? in_array($this->file_type, $allowedMimes, true) : ($allowedMimes === $this->file_type);
        }
        return false;
    }

    public function is_allowed_filesize(): bool { return ($this->max_size === 0 || $this->max_size > $this->file_size); }

    public function is_allowed_dimensions(): bool
    {
        if (!$this->is_image()) return true;
        if (!function_exists('getimagesize')) return true;
        $D = @getimagesize($this->file_temp);
        if ($D === false) return true;
        if ($this->max_width > 0 && $D[0] > $this->max_width) return false;
        if ($this->max_height > 0 && $D[1] > $this->max_height) return false;
        if ($this->min_width > 0 && $D[0] < $this->min_width) return false;
        if ($this->min_height > 0 && $D[1] < $this->min_height) return false;
        return true;
    }

    public function validate_upload_path(): bool
    {
        if ($this->upload_path === '') { $this->setError('upload_no_filepath', 'error'); return false; }
        if (realpath($this->upload_path) !== false) $this->upload_path = str_replace('\\', '/', realpath($this->upload_path));
        if (!is_dir($this->upload_path)) { $this->setError('upload_no_filepath', 'error'); return false; }
        if (!is_really_writable($this->upload_path)) { $this->setError('upload_not_writable', 'error'); return false; }
        $this->upload_path = preg_replace('/(.+?)\/*$/', '\1/', $this->upload_path);
        return true;
    }

    public function get_extension($filename): string
    {
        $x = explode('.', $filename);
        if (count($x) === 1) return '';
        $ext = $this->file_ext_tolower ? strtolower(end($x)) : end($x);
        return '.' . $ext;
    }

    public function limit_filename_length($filename, $length): string
    {
        if (strlen($filename) < $length) return $filename;
        $ext = '';
        if (strpos($filename, '.') !== false) {
            $parts = explode('.', $filename);
            $ext = '.' . array_pop($parts);
            $filename = implode('.', $parts);
        }
        return substr($filename, 0, ($length - strlen($ext))) . $ext;
    }

    public function do_xss_clean(): bool
    {
        $file = $this->file_temp;
        if (filesize($file) == 0) return false;
        if (memory_get_usage() && ($memory_limit = ini_get('memory_limit')) > 0) {
            $memory_limit = str_split($memory_limit, strspn($memory_limit, '1234567890'));
            if (!empty($memory_limit[1])) {
                switch ($memory_limit[1][0]) {
                    case 'g': case 'G': $memory_limit[0] *= 1024 * 1024 * 1024; break;
                    case 'm': case 'M': $memory_limit[0] *= 1024 * 1024; break;
                }
            }
            $memory_limit = (int) ceil(filesize($file) + $memory_limit[0]);
            ini_set('memory_limit', (string) $memory_limit);
        }
        if (function_exists('getimagesize') && @getimagesize($file) !== false) {
            if (($file = @fopen($file, 'rb')) === false) return false;
            $opening_bytes = fread($file, 256);
            fclose($file);
            return !preg_match('/<(a|body|head|html|img|plaintext|pre|script|table|title)[\s>]/i', $opening_bytes);
        }
        if (($data = @file_get_contents($file)) === false) return false;
        return $this->_CI->security->xss_clean($data, true);
    }

    protected function _prep_filename($filename): string
    {
        if ($this->mod_mime_fix === false || $this->allowed_types === '*' || ($ext_pos = strrpos($filename, '.')) === false) return $filename;
        $ext = substr($filename, $ext_pos);
        $filename = substr($filename, 0, $ext_pos);
        return str_replace('.', '_', $filename) . $ext;
    }

    protected function &getMimes(): array
    {
        static $mimes = null;
        if ($mimes === null) $mimes = function_exists('get_mimes') ? get_mimes() : [];
        return $mimes;
    }

    protected function getCI(): ?object
    {
        if (function_exists('kodhe')) return kodhe();
        if (function_exists('get_instance')) return get_instance();
        return null;
    }

    protected function logMessage(string $level, string $msg): void
    {
        if (function_exists('log_message')) log_message($level, $msg);
    }

    protected function set_max_size($n): self { return $this->set_max_filesize($n); }

    public function getStorage(): StorageInterface { return $this->storage; }
    public function setStorage(StorageInterface $storage): self { $this->storage = $storage; return $this; }
    public function getValidator(): FileValidator
    {
        if ($this->validator === null) {
            $this->validator = new FileValidator([
                'max_size' => $this->max_size,
                'allowed_types' => is_array($this->allowed_types) ? $this->allowed_types : [],
                'max_width' => $this->max_width,
                'max_height' => $this->max_height,
                'min_width' => $this->min_width,
                'min_height' => $this->min_height,
            ], $this->mimeCache);
        }
        return $this->validator;
    }
    public function setValidator(FileValidator $validator): self { $this->validator = $validator; return $this; }
    public function getUploadedFile(): ?UploadedFile { return $this->uploadedFile; }
    public function __destruct() { $this->mimeCache = null; }
}
