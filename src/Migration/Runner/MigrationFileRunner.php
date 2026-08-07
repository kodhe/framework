<?php

namespace Kodhe\Migration\Runner;

use Kodhe\Migration\Contracts\MigrationRunnerInterface;

/**
 * Runner untuk mengeksekusi migration files
 *
 * @package Kodhe\Migration\Runner
 */
class MigrationFileRunner implements MigrationRunnerInterface
{
    /**
     * @var string Path ke folder migrations
     */
    private string $migrationPath;

    /**
     * @var string|null Error message terakhir
     */
    private ?string $lastError = null;

    /**
     * Constructor
     *
     * @param string $migrationPath
     */
    public function __construct(string $migrationPath)
    {
        $this->migrationPath = rtrim($migrationPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Jalankan migration ke atas (up)
     *
     * @param string $file Nama file migration
     * @return bool
     */
    public function up(string $file): bool
    {
        $filePath = $this->migrationPath . $file;

        if (!file_exists($filePath)) {
            $this->lastError = "Migration file not found: {$file}";
            return false;
        }

        try {
            $this->lastError = null;
            
            // Include file migration
            require_once $filePath;

            // Extract class name dari filename
            $className = $this->getClassName($file);

            if (!class_exists($className)) {
                $this->lastError = "Migration class not found: {$className}";
                return false;
            }

            // Instantiate dan jalankan up()
            $migration = new $className();
            
            if (!method_exists($migration, 'up')) {
                $this->lastError = "Migration class {$className} missing up() method";
                return false;
            }

            $migration->up();
            return true;

        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Jalankan migration ke bawah (down/rollback)
     *
     * @param string $file Nama file migration
     * @return bool
     */
    public function down(string $file): bool
    {
        $filePath = $this->migrationPath . $file;

        if (!file_exists($filePath)) {
            $this->lastError = "Migration file not found: {$file}";
            return false;
        }

        try {
            $this->lastError = null;
            
            // Include file migration
            require_once $filePath;

            // Extract class name dari filename
            $className = $this->getClassName($file);

            if (!class_exists($className)) {
                $this->lastError = "Migration class not found: {$className}";
                return false;
            }

            // Instantiate dan jalankan down()
            $migration = new $className();
            
            if (!method_exists($migration, 'down')) {
                $this->lastError = "Migration class {$className} missing down() method";
                return false;
            }

            $migration->down();
            return true;

        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Get error message terakhir
     *
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Get class name dari filename migration
     *
     * @param string $file
     * @return string
     */
    private function getClassName(string $file): string
    {
        // Format: 1234567890_create_users.php -> Migration_1234567890_create_users
        $basename = pathinfo($file, PATHINFO_FILENAME);
        return 'Migration_' . $basename;
    }
}
