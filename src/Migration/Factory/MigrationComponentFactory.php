<?php

namespace Kodhe\Migration\Factory;

use CI_DB_forge;
use Kodhe\Migration\Contracts\MigrationRepositoryInterface;
use Kodhe\Migration\Contracts\MigrationRunnerInterface;
use Kodhe\Migration\Repository\FileMigrationRepository;
use Kodhe\Migration\Runner\MigrationFileRunner;

/**
 * Factory untuk membuat instance migration components
 *
 * @package Kodhe\Migration\Factory
 */
class MigrationComponentFactory
{
    /**
     * Buat repository instance
     *
     * @param string $migrationPath
     * @param CI_DB_forge|null $dbForge
     * @return MigrationRepositoryInterface
     */
    public static function makeRepository(
        string $migrationPath,
        ?CI_DB_forge $dbForge = null
    ): MigrationRepositoryInterface {
        return new FileMigrationRepository($migrationPath, $dbForge);
    }

    /**
     * Buat runner instance
     *
     * @param string $migrationPath
     * @return MigrationRunnerInterface
     */
    public static function makeRunner(string $migrationPath): MigrationRunnerInterface
    {
        return new MigrationFileRunner($migrationPath);
    }

    /**
     * Buat repository dan runner dalam array
     *
     * @param string $migrationPath
     * @param CI_DB_forge|null $dbForge
     * @return array{repository: MigrationRepositoryInterface, runner: MigrationRunnerInterface}
     */
    public static function makeComponents(
        string $migrationPath,
        ?CI_DB_forge $dbForge = null
    ): array {
        return [
            'repository' => self::makeRepository($migrationPath, $dbForge),
            'runner' => self::makeRunner($migrationPath),
        ];
    }
}
