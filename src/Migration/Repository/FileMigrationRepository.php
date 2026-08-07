<?php

namespace Kodhe\Migration\Repository;

use CI_DB_forge;
use Kodhe\Migration\Contracts\MigrationRepositoryInterface;
use Kodhe\Migration\Parser\MigrationFilenameParser;
use Kodhe\Migration\Exceptions\InvalidMigrationFileException;

/**
 * Repository untuk mengelola migration files dan metadata
 *
 * @package Kodhe\Migration\Repository
 */
class FileMigrationRepository implements MigrationRepositoryInterface
{
    /**
     * @var string Path ke folder migrations
     */
    private string $migrationPath;

    /**
     * @var CI_DB_forge|null Database forge instance
     */
    private ?CI_DB_forge $dbForge = null;

    /**
     * @var string Nama table untuk menyimpan migration log
     */
    private string $tableName = 'migrations';

    /**
     * @var MigrationFilenameParser Parser untuk filename
     */
    private MigrationFilenameParser $parser;

    /**
     * @var array Cache untuk migration yang sudah di-scan
     */
    private static array $scanCache = [];

    /**
     * @var array Cache untuk migration yang sudah dijalankan
     */
    private ?array $ranCache = null;

    /**
     * Constructor
     *
     * @param string $migrationPath
     * @param CI_DB_forge|null $dbForge
     */
    public function __construct(
        string $migrationPath,
        ?CI_DB_forge $dbForge = null
    ) {
        $this->migrationPath = rtrim($migrationPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->dbForge = $dbForge;
        $this->parser = new MigrationFilenameParser();
    }

    /**
     * Set database forge
     *
     * @param CI_DB_forge $dbForge
     * @return void
     */
    public function setDbForge(CI_DB_forge $dbForge): void
    {
        $this->dbForge = $dbForge;
    }

    /**
     * Get daftar semua migration yang tersedia
     *
     * @return array
     */
    public function getAvailable(): array
    {
        $cacheKey = $this->migrationPath;

        if (isset(self::$scanCache[$cacheKey])) {
            return self::$scanCache[$cacheKey];
        }

        $files = [];
        if (!is_dir($this->migrationPath)) {
            self::$scanCache[$cacheKey] = $files;
            return $files;
        }

        $dirIterator = new \DirectoryIterator($this->migrationPath);

        foreach ($dirIterator as $fileInfo) {
            if ($fileInfo->isFile() && $fileInfo->getExtension() === 'php') {
                $filename = $fileInfo->getFilename();
                if ($this->parser->isValid($filename)) {
                    $version = $this->parser->getVersion($filename);
                    $files[$version] = $filename;
                }
            }
        }

        // Sort by version
        ksort($files);
        self::$scanCache[$cacheKey] = $files;

        return $files;
    }

    /**
     * Get daftar migration yang sudah dijalankan
     *
     * @return array
     */
    public function getRan(): array
    {
        if ($this->ranCache !== null) {
            return $this->ranCache;
        }

        if (!$this->repositoryExists()) {
            $this->ranCache = [];
            return [];
        }

        $ci = get_instance();
        $query = $ci->db->select('migration')->order_by('migration', 'asc')->get($this->tableName);
        
        $this->ranCache = $query->result_array();
        $this->ranCache = array_column($this->ranCache, 'migration');

        return $this->ranCache;
    }

    /**
     * Get daftar migration yang belum dijalankan
     *
     * @return array
     */
    public function getPending(): array
    {
        $available = $this->getAvailable();
        $ran = $this->getRan();

        return array_diff_key($available, array_flip($ran));
    }

    /**
     * Mark migration sebagai sudah dijalankan
     *
     * @param string $file
     * @param int $batch
     * @return void
     */
    public function log(string $file, int $batch): void
    {
        if (!$this->repositoryExists()) {
            $this->createRepository();
        }

        $version = $this->parser->getVersion($file);
        if ($version === null) {
            throw new InvalidMigrationFileException($file);
        }

        $ci = get_instance();
        $ci->db->insert($this->tableName, [
            'migration' => $version,
            'batch' => $batch,
        ]);

        // Invalidate cache
        $this->ranCache = null;
    }

    /**
     * Hapus migration dari daftar yang sudah dijalankan (rollback)
     *
     * @param string $file
     * @return void
     */
    public function delete(string $file): void
    {
        if (!$this->repositoryExists()) {
            return;
        }

        $version = $this->parser->getVersion($file);
        if ($version === null) {
            return;
        }

        $ci = get_instance();
        $ci->db->where('migration', $version)->delete($this->tableName);

        // Invalidate cache
        $this->ranCache = null;
    }

    /**
     * Get batch number terakhir
     *
     * @return int|null
     */
    public function getLastBatchNumber(): ?int
    {
        if (!$this->repositoryExists()) {
            return null;
        }

        $ci = get_instance();
        $query = $ci->db->select_max('batch')->get($this->tableName);
        $row = $query->row();

        return $row->batch ?? null;
    }

    /**
     * Get semua migration dalam batch tertentu
     *
     * @param int $batch
     * @return array
     */
    public function getMigrationsByBatch(int $batch): array
    {
        if (!$this->repositoryExists()) {
            return [];
        }

        $ci = get_instance();
        $query = $ci->db->where('batch', $batch)
                        ->order_by('migration', 'desc')
                        ->get($this->tableName);

        return array_column($query->result_array(), 'migration');
    }

    /**
     * Check apakah migration table sudah ada
     *
     * @return bool
     */
    public function repositoryExists(): bool
    {
        $ci = get_instance();
        return $ci->db->table_exists($this->tableName);
    }

    /**
     * Buat migration repository (table)
     *
     * @return void
     */
    public function createRepository(): void
    {
        if ($this->dbForge === null) {
            $ci = get_instance();
            $this->dbForge = $ci->load->dbforge();
        }

        if ($this->dbForge === null) {
            throw new \RuntimeException('Database forge not available');
        }

        $this->dbForge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'migration' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'batch' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
        ]);

        $this->dbForge->add_key('id', true);
        $this->dbForge->add_key('batch');
        $this->dbForge->create_table($this->tableName, true);
    }

    /**
     * Get nama migration file dari version
     *
     * @param int $version
     * @return string|null
     */
    public function getMigrationNameByVersion(int $version): ?string
    {
        $available = $this->getAvailable();
        return $available[$version] ?? null;
    }

    /**
     * Clear scan cache (untuk testing)
     *
     * @return void
     */
    public function clearCache(): void
    {
        self::$scanCache = [];
        $this->ranCache = null;
    }
}
