<?php

namespace Kodhe\Migration\Tests;

use Kodhe\Migration\Repository\FileMigrationRepository;
use PHPUnit\Framework\TestCase;

/**
 * Test case untuk FileMigrationRepository
 *
 * @package Kodhe\Migration\Tests
 */
class FileMigrationRepositoryTest extends TestCase
{
    private string $testPath;
    private FileMigrationRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testPath = __DIR__ . '/fixtures/repository/';
        
        if (!is_dir($this->testPath)) {
            mkdir($this->testPath, 0755, true);
        }

        $this->repository = new FileMigrationRepository($this->testPath);
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

    private function createTestFile(int $version, string $name): string
    {
        $filename = sprintf('%d_%s.php', $version, $name);
        file_put_contents($this->testPath . $filename, '<?php // test');
        return $filename;
    }

    public function testGetAvailableReturnsEmptyArrayWhenNoFiles(): void
    {
        $files = $this->repository->getAvailable();
        
        $this->assertIsArray($files);
        $this->assertEmpty($files);
    }

    public function testGetAvailableReturnsSortedMigrations(): void
    {
        $this->createTestFile(3, 'third');
        $this->createTestFile(1, 'first');
        $this->createTestFile(2, 'second');

        $files = $this->repository->getAvailable();
        
        $this->assertCount(3, $files);
        $this->assertEquals([1, 2, 3], array_keys($files));
    }

    public function testGetAvailableIgnoresInvalidFilenames(): void
    {
        $this->createTestFile(1, 'valid');
        file_put_contents($this->testPath . 'invalid.php', '<?php // invalid');
        file_put_contents($this->testPath . 'readme.txt', 'readme');

        $files = $this->repository->getAvailable();
        
        $this->assertCount(1, $files);
        $this->assertArrayHasKey(1, $files);
    }

    public function testGetPendingReturnsUnrunMigrations(): void
    {
        $this->createTestFile(1, 'first');
        $this->createTestFile(2, 'second');

        // Simulate first migration already run
        $this->repository->log($this->createTestFile(1, 'first'), 1);

        $pending = $this->repository->getPending();
        
        $this->assertCount(1, $pending);
        $this->assertArrayHasKey(2, $pending);
    }

    public function testLogAddsMigrationToRanList(): void
    {
        $filename = $this->createTestFile(1, 'test_migration');
        
        $this->repository->log($filename, 1);
        
        $ran = $this->repository->getRan();
        $this->assertContains(1, $ran);
    }

    public function testDeleteRemovesMigrationFromRanList(): void
    {
        $filename = $this->createTestFile(1, 'test_migration');
        
        $this->repository->log($filename, 1);
        $this->repository->delete($filename);
        
        $ran = $this->repository->getRan();
        $this->assertNotContains(1, $ran);
    }

    public function testGetLastBatchNumberReturnsNullWhenNoMigrations(): void
    {
        $batch = $this->repository->getLastBatchNumber();
        
        $this->assertNull($batch);
    }

    public function testGetLastBatchNumberReturnsCorrectBatch(): void
    {
        $file1 = $this->createTestFile(1, 'first');
        $file2 = $this->createTestFile(2, 'second');
        
        $this->repository->log($file1, 1);
        $this->repository->log($file2, 1);
        $this->repository->log($this->createTestFile(3, 'third'), 2);

        $batch = $this->repository->getLastBatchNumber();
        
        $this->assertEquals(2, $batch);
    }

    public function testGetMigrationsByBatchReturnsCorrectMigrations(): void
    {
        $file1 = $this->createTestFile(1, 'first');
        $file2 = $this->createTestFile(2, 'second');
        $file3 = $this->createTestFile(3, 'third');
        
        $this->repository->log($file1, 1);
        $this->repository->log($file2, 1);
        $this->repository->log($file3, 2);

        $batch1Migrations = $this->repository->getMigrationsByBatch(1);
        $batch2Migrations = $this->repository->getMigrationsByBatch(2);
        
        $this->assertCount(2, $batch1Migrations);
        $this->assertCount(1, $batch2Migrations);
        $this->assertContains(2, $batch1Migrations);
        $this->assertContains(3, $batch2Migrations);
    }

    public function testGetMigrationNameByVersionReturnsCorrectName(): void
    {
        $filename = $this->createTestFile(42, 'create_users');
        
        $result = $this->repository->getMigrationNameByVersion(42);
        
        $this->assertEquals($filename, $result);
    }

    public function testGetMigrationNameByVersionReturnsNullForNonExistentVersion(): void
    {
        $result = $this->repository->getMigrationNameByVersion(999);
        
        $this->assertNull($result);
    }

    public function testCacheIsUsedForSubsequentCalls(): void
    {
        $this->createTestFile(1, 'test');
        
        // First call populates cache
        $first = $this->repository->getAvailable();
        
        // Second call should use cache
        $second = $this->repository->getAvailable();
        
        $this->assertSame($first, $second);
    }

    public function testClearCacheResetsCachedData(): void
    {
        $this->createTestFile(1, 'test');
        
        $first = $this->repository->getAvailable();
        
        // Create new file after first call
        $this->createTestFile(2, 'test2');
        
        // Should still show cached data (1 file)
        $cached = $this->repository->getAvailable();
        $this->assertCount(1, $cached);
        
        // Clear cache
        $this->repository->clearCache();
        
        // Now should show both files
        $fresh = $this->repository->getAvailable();
        $this->assertCount(2, $fresh);
    }

    public function testRepositoryExistsReturnsFalseInitially(): void
    {
        $exists = $this->repository->repositoryExists();
        
        // Should be false since we don't have real DB in tests
        $this->assertFalse($exists);
    }
}
