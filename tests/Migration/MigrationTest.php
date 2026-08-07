<?php

namespace Kodhe\Migration\Tests;

use Kodhe\Migration\Migration;
use Kodhe\Migration\Repository\FileMigrationRepository;
use Kodhe\Migration\Runner\MigrationFileRunner;
use PHPUnit\Framework\TestCase;

/**
 * Test case untuk Migration library
 *
 * @package Kodhe\Migration\Tests
 */
class MigrationTest extends TestCase
{
    private string $testMigrationPath;
    private Migration $migration;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup test migration path
        $this->testMigrationPath = __DIR__ . '/../../tests/Migration/fixtures/';
        
        if (!is_dir($this->testMigrationPath)) {
            mkdir($this->testMigrationPath, 0755, true);
        }

        // Mock CI instance jika diperlukan
        $this->mockCIInstance();
        
        // Create migration instance
        $this->migration = new Migration([
            'migration_path' => $this->testMigrationPath,
            'migration_enabled' => true,
        ]);
    }

    protected function tearDown(): void
    {
        // Cleanup test migrations
        $this->cleanupTestMigrations();
        
        parent::tearDown();
    }

    private function mockCIInstance(): void
    {
        // Mock CI instance for testing
        if (!function_exists('get_instance')) {
            function get_instance() {
                static $ci = null;
                
                if ($ci === null) {
                    $ci = new \stdClass();
                    
                    // Mock database
                    $ci->db = new class {
                        public function table_exists($table) {
                            return false; // Simulate table doesn't exist initially
                        }
                        
                        public function select($fields) {
                            return $this;
                        }
                        
                        public function order_by($field, $order) {
                            return $this;
                        }
                        
                        public function get($table) {
                            return new class {
                                public function result_array() {
                                    return [];
                                }
                                
                                public function row() {
                                    return (object) ['batch' => null];
                                }
                            };
                        }
                        
                        public function select_max($field) {
                            return $this;
                        }
                        
                        public function where($key, $value = null) {
                            return $this;
                        }
                        
                        public function delete($table) {
                            return true;
                        }
                        
                        public function insert($table, $data) {
                            return true;
                        }
                    };
                    
                    // Mock dbforge
                    $ci->dbforge = new class {
                        public function add_field($fields) {
                            return $this;
                        }
                        
                        public function add_key($key, $primary = false) {
                            return $this;
                        }
                        
                        public function create_table($table, $if_not_exists = false) {
                            return true;
                        }
                    };
                }
                
                return $ci;
            }
        }
    }

    private function cleanupTestMigrations(): void
    {
        if (is_dir($this->testMigrationPath)) {
            $files = glob($this->testMigrationPath . '*.php');
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir($this->testMigrationPath);
        }
    }

    private function createTestMigration(int $version, string $name, bool $shouldFail = false): string
    {
        $filename = sprintf('%012d_%s.php', $version, $name);
        $filepath = $this->testMigrationPath . $filename;
        
        $content = '<?php

class Migration_' . $version . '_' . $name . '
{
    public function up()
    {
        ' . ($shouldFail ? 'throw new \Exception("Intentional failure");' : '// Migration up') . '
    }

    public function down()
    {
        ' . ($shouldFail ? 'throw new \Exception("Intentional failure");' : '// Migration down') . '
    }
}
';
        
        file_put_contents($filepath, $content);
        return $filename;
    }

    public function testLatestWithNoMigrations(): void
    {
        $result = $this->migration->latest();
        
        $this->assertFalse($result);
        $this->assertStringContainsString('No migrations found', $this->migration->error_string());
    }

    public function testLatestRunsAllMigrations(): void
    {
        // Create test migrations
        $this->createTestMigration(1, 'create_users');
        $this->createTestMigration(2, 'create_posts');
        $this->createTestMigration(3, 'add_email_to_users');

        $result = $this->migration->latest();
        
        $this->assertTrue($result);
        $this->assertEquals(3, $this->migration->getCurrentVersion());
    }

    public function testVersionMigratesToSpecificVersion(): void
    {
        // Create test migrations
        $this->createTestMigration(1, 'create_users');
        $this->createTestMigration(2, 'create_posts');
        $this->createTestMigration(3, 'add_email_to_users');

        // Migrate to version 2 only
        $result = $this->migration->version(2);
        
        $this->assertTrue($result);
        $this->assertEquals(2, $this->migration->getCurrentVersion());
    }

    public function testRollbackDecrementsBatch(): void
    {
        // Create and run migrations
        $this->createTestMigration(1, 'create_users');
        $this->createTestMigration(2, 'create_posts');
        
        $this->migration->latest();
        $this->assertEquals(2, $this->migration->getCurrentVersion());

        // Rollback
        $result = $this->migration->rollback();
        
        $this->assertTrue($result);
        $this->assertEquals(0, $this->migration->getCurrentVersion());
    }

    public function testFindMigrationsReturnsAvailableMigrations(): void
    {
        // Create test migrations
        $this->createTestMigration(1, 'create_users');
        $this->createTestMigration(2, 'create_posts');

        $migrations = $this->migration->find_migrations();
        
        $this->assertCount(2, $migrations);
        $this->assertArrayHasKey(1, $migrations);
        $this->assertArrayHasKey(2, $migrations);
    }

    public function testCurrentIsAliasForVersion(): void
    {
        $this->createTestMigration(1, 'create_users');
        
        $versionResult = $this->migration->version(1);
        $this->assertTrue($versionResult);
        
        // Reset and test current()
        $this->cleanupTestMigrations();
        mkdir($this->testMigrationPath, 0755, true);
        $this->createTestMigration(1, 'create_users');
        
        $currentResult = $this->migration->current(1);
        $this->assertTrue($currentResult);
    }

    public function testErrorStringReturnsErrorMessage(): void
    {
        $result = $this->migration->latest();
        
        $this->assertFalse($result);
        $this->assertNotNull($this->migration->error_string());
    }

    public function testDisabledMigrationFails(): void
    {
        $migration = new Migration([
            'migration_path' => $this->testMigrationPath,
            'migration_enabled' => false,
        ]);

        $result = $migration->latest();
        
        $this->assertFalse($result);
        $this->assertStringContainsString('disabled', $migration->error_string());
    }

    public function testVersionZeroWhenNoMigrationsRun(): void
    {
        $version = $this->migration->getCurrentVersion();
        
        $this->assertEquals(0, $version);
    }

    public function testMigrateUpThenDown(): void
    {
        $this->createTestMigration(1, 'create_users');
        
        // Migrate up
        $upResult = $this->migration->version(1);
        $this->assertTrue($upResult);
        $this->assertEquals(1, $this->migration->getCurrentVersion());

        // Migrate down to 0
        $downResult = $this->migration->version(0);
        $this->assertTrue($downResult);
        $this->assertEquals(0, $this->migration->getCurrentVersion());
    }
}
