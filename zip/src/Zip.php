<?php

declare(strict_types=0);

namespace Kodhe\Framework\Zip;

use Kodhe\Framework\Zip\Archive\ArchiveBuilder;
use Kodhe\Framework\Zip\Writers\MemoryWriter;
use Kodhe\Framework\Zip\Readers\FileSystemReader;
use Kodhe\Framework\Zip\Factory\CompressionFactory;
use Kodhe\Framework\Zip\Support\ByteUtils;

/**
 * Zip Compression Class - Backward Compatible Facade
 *
 * This class provides 100% backward compatibility with CodeIgniter 3's Zip library
 * while using the new modular architecture internally.
 *
 * Original library found at Zend:
 * http://www.zend.com/codex.php?id=696&single=1
 *
 * @package     Kodhe\Zip
 * @category    Compression
 * @author      EllisLab Dev Team (original), Kodhe (refactored)
 * @link        https://codeigniter.com/user_guide/libraries/zip.html
 */
class Zip
{
    /**
     * Zip data in string form (backward compatibility)
     *
     * @var string
     */
    public $zipdata = '';

    /**
     * Zip data for a directory in string form (backward compatibility)
     *
     * @var string
     */
    public $directory = '';

    /**
     * Number of files/folder in zip file (backward compatibility)
     *
     * @var int
     */
    public $entries = 0;

    /**
     * Number of files in zip (backward compatibility)
     *
     * @var int
     */
    public $file_num = 0;

    /**
     * relative offset of local header (backward compatibility)
     *
     * @var int
     */
    public $offset = 0;

    /**
     * Reference to time at init (backward compatibility)
     *
     * @var int
     */
    public $now;

    /**
     * The level of compression (backward compatibility)
     *
     * Ranges from 0 to 9, with 9 being the highest level.
     *
     * @var int
     */
    public $compression_level = 2;

    /**
     * Internal archive builder instance
     *
     * @var ArchiveBuilder
     */
    private ArchiveBuilder $builder;

    /**
     * File reader instance
     *
     * @var FileSystemReader
     */
    private FileSystemReader $fileReader;

    /**
     * mbstring.func_overload flag
     *
     * @var bool|null
     */
    protected static $func_overload;

    /**
     * Initialize zip compression class
     *
     * @return void
     */
    public function __construct()
    {
        // Initialize mbstring overload detection
        if (self::$func_overload === null) {
            self::$func_overload = (extension_loaded('mbstring') && ini_get('mbstring.func_overload'));
        }

        $this->now = time();

        // Initialize internal components with dependency injection
        $this->fileReader = new FileSystemReader();
        $writer = new MemoryWriter();
        $compressionStrategy = CompressionFactory::create($this->compression_level);
        
        $this->builder = new ArchiveBuilder($writer, $this->fileReader, $compressionStrategy);

        // Log initialization if CI log function exists
        if (function_exists('log_message')) {
            log_message('info', 'Zip Compression Class Initialized');
        }
    }

    // --------------------------------------------------------------------

    /**
     * Add Directory
     *
     * Lets you add a virtual directory into which you can place files.
     *
     * @param mixed $directory the directory name. Can be string or array
     * @return void
     */
    public function add_dir($directory)
    {
        foreach ((array) $directory as $dir) {
            if (!preg_match('|.+/$|', $dir)) {
                $dir .= '/';
            }

            // Use builder to add directory
            $this->builder->addDirectory($dir);
            
            // Update backward compatibility properties
            $this->entries = $this->builder->getEntryCount();
        }
    }

    // --------------------------------------------------------------------

    /**
     * Get file/directory modification time
     *
     * If this is a newly created file/dir, we will set the time to 'now'
     *
     * @param string $dir path to file
     * @return array filemtime/filemdate
     */
    protected function _get_mod_time($dir)
    {
        // filemtime() may return false, but raises an error for non-existing files
        $date = file_exists($dir) ? getdate(filemtime($dir)) : getdate($this->now);

        return [
            'file_mtime' => ($date['hours'] << 11) + ($date['minutes'] << 5) + (int)($date['seconds'] / 2),
            'file_mdate' => (($date['year'] - 1980) << 9) + ($date['mon'] << 5) + $date['mday']
        ];
    }

    // --------------------------------------------------------------------

    /**
     * Add Data to Zip
     *
     * Lets you add files to the archive. If the path is included
     * in the filename it will be placed within a directory. Make
     * sure you use add_dir() first to create the folder.
     *
     * @param mixed $filepath A single filepath or an array of file => data pairs
     * @param string $data Single file contents
     * @return void
     */
    public function add_data($filepath, $data = NULL)
    {
        if (is_array($filepath)) {
            foreach ($filepath as $path => $data) {
                $this->builder->addFile($path, $data);
            }
        } else {
            $this->builder->addFile($filepath, $data);
        }

        // Update backward compatibility properties
        $this->entries = $this->builder->getEntryCount();
        $this->file_num = $this->countFiles();
    }

    // --------------------------------------------------------------------

    /**
     * Count number of file entries (not directories)
     */
    private function countFiles(): int
    {
        $count = 0;
        foreach ($this->builder->getEntries() as $entry) {
            if (!$entry->isDirectory()) {
                $count++;
            }
        }
        return $count;
    }

    // --------------------------------------------------------------------

    /**
     * Read the contents of a file and add it to the zip
     *
     * @param string $path
     * @param bool|string $archive_filepath
     * @return bool
     */
    public function read_file($path, $archive_filepath = FALSE)
    {
        if ($this->fileReader->exists($path) && ($data = $this->fileReader->read($path)) !== false) {
            if (is_string($archive_filepath)) {
                $name = str_replace('\\', '/', $archive_filepath);
            } else {
                $name = str_replace('\\', '/', $path);

                if ($archive_filepath === FALSE) {
                    $name = preg_replace('|.*/(.+)|', '\\1', $name);
                }
            }

            $this->add_data($name, $data);
            return TRUE;
        }

        return FALSE;
    }

    // ------------------------------------------------------------------------

    /**
     * Read a directory and add it to the zip.
     *
     * This function recursively reads a folder and everything it contains (including
     * sub-folders) and creates a zip based on it. Whatever directory structure
     * is in the original file path will be recreated in the zip file.
     *
     * @param string $path path to source directory
     * @param bool $preserve_filepath
     * @param string|null $root_path
     * @return bool
     */
    public function read_dir($path, $preserve_filepath = TRUE, $root_path = NULL)
    {
        $result = $this->builder->readDirectory($path, $preserve_filepath, $root_path);
        
        // Update backward compatibility properties
        $this->entries = $this->builder->getEntryCount();
        $this->file_num = $this->countFiles();
        
        return $result;
    }

    // --------------------------------------------------------------------

    /**
     * Get the Zip file
     *
     * @return string|false (binary encoded)
     */
    public function get_zip()
    {
        // Is there any data to return?
        if ($this->entries === 0) {
            return FALSE;
        }

        // Build the archive using the builder
        $zipContent = $this->builder->build();
        
        // Cache for backward compatibility
        $this->zipdata = $zipContent;
        
        return $zipContent;
    }

    // --------------------------------------------------------------------

    /**
     * Write File to the specified directory
     *
     * Lets you write a file
     *
     * @param string $filepath the file name
     * @return bool
     */
    public function archive($filepath)
    {
        $zipContent = $this->get_zip();
        if ($zipContent === false || $zipContent === '') {
            return FALSE;
        }

        if (!($fp = @fopen($filepath, 'w+b'))) {
            return FALSE;
        }

        flock($fp, LOCK_EX);

        for ($result = $written = 0, $length = strlen($zipContent); $written < $length; $written += $result) {
            if (($result = fwrite($fp, substr($zipContent, $written))) === FALSE) {
                break;
            }
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        return is_int($result);
    }

    // --------------------------------------------------------------------

    /**
     * Download
     *
     * @param string $filename the file name
     * @return void
     */
    public function download($filename = 'backup.zip')
    {
        if (!preg_match('|.+?\.zip$|', $filename)) {
            $filename .= '.zip';
        }

        // Check if kodhe() helper exists (CI3/Kodhe framework)
        if (function_exists('kodhe')) {
            kodhe()->load->helper('download');
        } elseif (function_exists('load_helper')) {
            load_helper('download');
        }

        $zipContent = $this->get_zip();
        
        // Use force_download if available, otherwise output directly
        if (function_exists('force_download')) {
            force_download($filename, $zipContent);
        } else {
            // Fallback: direct output
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
            header('Content-Length: ' . strlen($zipContent));
            header('Cache-Control: no-cache, must-revalidate');
            echo $zipContent;
            exit;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Initialize Data
     *
     * Lets you clear current zip data. Useful if you need to create
     * multiple zips with different data.
     *
     * @return Zip
     */
    public function clear_data()
    {
        $this->builder->clear();
        $this->zipdata = '';
        $this->directory = '';
        $this->entries = 0;
        $this->file_num = 0;
        $this->offset = 0;
        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Byte-safe strlen()
     *
     * @param string $str
     * @return int
     */
    protected static function strlen($str)
    {
        return ByteUtils::strlen($str);
    }

    // --------------------------------------------------------------------

    /**
     * Byte-safe substr()
     *
     * @param string $str
     * @param int $start
     * @param int|null $length
     * @return string
     */
    protected static function substr($str, $start, $length = NULL)
    {
        return ByteUtils::substr($str, $start, $length);
    }
}
