<?php

namespace Kodhe\Framework\Migration\Tests;

use Kodhe\Framework\Migration\Runner\MigrationFileRunner;
use PHPUnit\Framework\TestCase;

/**
 * Test case untuk MigrationFileRunner
 *
 * @package Kodhe\Migration\Tests
 */
class MigrationFileRunnerTest extends TestCase
{
    private string $testPath;
    private MigrationFileRunner $runner;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testPath = __DIR__ . '/fixtures/runner/';
        
        if (!is_dir($this->testPath)) {
            mkdir($this->testPath, 0755, true);
        }

        $this->runner = new MigrationFileRunner($this->testPath);
    }

    protected function tearDown(): void
    {
        $this->cleanupTestFiles();
        parent::tearDown();
    }

    private function cleanupTestFiles(): void
    {
        if (is_dir($this->testPath)) {
            $files = glob($this->testPath . '*.php');
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir($this->testPath);
        }
    }

    private function createTestMigration(int $version, string $name, bool $hasDown = true, bool $shouldFail = false): string
    {
        $filename = sprintf('%d_%s.php', $version, $name);
        $filepath = $this->testPath . $filename;
        
        $downMethod = $hasDown ? 'public function down() { ' . ($shouldFail ? 'throw new \Exception("Fail down");' : '// down') . ' }' : '';
        
        $content = '<?php

class Migration_' . $version . '_' . $name . '
{
    public function up()
    {
        ' . ($shouldFail ? 'throw new \Exception("Fail up");' : '// up') . '
    }

    ' . $downMethod . '
}
';
        
        file_put_contents($filepath, $content);
        return $filename;
    }

    public function testUpRunsSuccessfully(): void
    {
        $filename = $this->createTestMigration(1, 'test_migration');
        
        $result = $this->runner->up($filename);
        
        $this->assertTrue($result);
        $this->assertNull($this->runner->getError());
    }

    public function testUpWithNonExistentFileReturnsFalse(): void
    {
        $result = $this->runner->up('999_nonexistent.php');
        
        $this->assertFalse($result);
        $this->assertStringContainsString('not found', $this->runner->getError());
    }

    public function testDownRunsSuccessfully(): void
    {
        $filename = $this->createTestMigration(1, 'test_migration');
        
        $result = $this->runner->down($filename);
        
        $this->assertTrue($result);
        $this->assertNull($this->runner->getError());
    }

    public function testUpWithFailingMigrationReturnsFalse(): void
    {
        $filename = $this->createTestMigration(1, 'failing_migration', true, true);
        
        $result = $this->runner->up($filename);
        
        $this->assertFalse($result);
        $this->assertNotNull($this->runner->getError());
    }

    public function testDownWithFailingMigrationReturnsFalse(): void
    {
        $filename = $this->createTestMigration(1, 'failing_down', true, true);
        
        $result = $this->runner->down($filename);
        
        $this->assertFalse($result);
        $this->assertNotNull($this->runner->getError());
    }

    public function testUpWithMissingDownMethodStillSucceedsForUp(): void
    {
        $filename = $this->createTestMigration(1, 'no_down_method', false);
        
        $result = $this->runner->up($filename);
        
        $this->assertTrue($result);
    }

    public function testDownWithMissingDownMethodReturnsFalse(): void
    {
        $filename = $this->createTestMigration(1, 'no_down_method', false);
        
        $result = $this->runner->down($filename);
        
        $this->assertFalse($result);
        $this->assertStringContainsString('missing down', strtolower($this->runner->getError()));
    }

    public function testGetErrorReturnsLastErrorMessage(): void
    {
        $this->runner->up('999_nonexistent.php');
        
        $error = $this->runner->getError();
        $this->assertNotNull($error);
        $this->assertStringContainsString('not found', $error);
    }

    public function testMultipleMigrationsRunInSequence(): void
    {
        $file1 = $this->createTestMigration(1, 'first');
        $file2 = $this->createTestMigration(2, 'second');
        $file3 = $this->createTestMigration(3, 'third');
        
        $this->assertTrue($this->runner->up($file1));
        $this->assertTrue($this->runner->up($file2));
        $this->assertTrue($this->runner->up($file3));
    }

    public function testRollbackInReverseOrder(): void
    {
        $file1 = $this->createTestMigration(1, 'first');
        $file2 = $this->createTestMigration(2, 'second');
        
        // Run both
        $this->runner->up($file1);
        $this->runner->up($file2);
        
        // Rollback in reverse order
        $this->assertTrue($this->runner->down($file2));
        $this->assertTrue($this->runner->down($file1));
    }

    public function testClassNameExtractedCorrectly(): void
    {
        // This is tested implicitly through successful execution
        $filename = $this->createTestMigration(123456, 'complex_name_test');
        
        $result = $this->runner->up($filename);
        $this->assertTrue($result);
    }
}
