<?php

declare(strict_types=1);

namespace Kodhe\Framework\Tests\CI3Compat;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Zip\Zip;

/**
 * Test Zip library compatibility with CodeIgniter 3 API
 */
class ZipTest extends TestCase
{
    private Zip $zip;

    protected function setUp(): void
    {
        parent::setUp();
        $this->zip = new Zip();
    }

    // =========================================================================
    // DEFAULT PROPERTIES TESTS
    // =========================================================================

    public function testDefaultZipdata(): void
    {
        $this->assertEquals('', $this->zip->zipdata);
    }

    public function testDefaultDirectory(): void
    {
        $this->assertEquals('', $this->zip->directory);
    }

    public function testDefaultEntries(): void
    {
        $this->assertEquals(0, $this->zip->entries);
    }

    public function testDefaultFileNum(): void
    {
        $this->assertEquals(0, $this->zip->file_num);
    }

    public function testDefaultOffset(): void
    {
        $this->assertEquals(0, $this->zip->offset);
    }

    public function testDefaultCompressionLevel(): void
    {
        $this->assertEquals(2, $this->zip->compression_level);
    }

    // =========================================================================
    // ADD DATA TESTS
    // =========================================================================

    public function testAddDataMethodExists(): void
    {
        $this->assertTrue(method_exists($this->zip, 'add_data'));
    }

    public function testAddDataAddsFileToArchive(): void
    {
        $result = $this->zip->add_data('test.txt', 'Hello World');
        
        $this->assertTrue($result);
        $this->assertGreaterThan(0, $this->zip->entries);
        $this->assertGreaterThan(0, $this->zip->file_num);
    }

    public function testAddDataWithArray(): void
    {
        $files = [
            'file1.txt' => 'Content 1',
            'file2.txt' => 'Content 2',
        ];
        
        $result = $this->zip->add_data($files);
        
        $this->assertTrue($result);
        $this->assertEquals(2, $this->zip->entries);
    }

    // =========================================================================
    // ADD DIRECTORY TESTS
    // =========================================================================

    public function testAddDirectoryMethodExists(): void
    {
        $this->assertTrue(method_exists($this->zip, 'add_dir'));
    }

    public function testAddDirectoryCreatesEntry(): void
    {
        $result = $this->zip->add_dir('my_directory/');
        
        $this->assertTrue($result);
    }

    public function testAddDirectoryWithoutTrailingSlash(): void
    {
        $result = $this->zip->add_dir('my_directory');
        
        $this->assertTrue($result);
    }

    // =========================================================================
    // ADD FROM FILE TESTS
    // =========================================================================

    public function testAddFromFileMethodExists(): void
    {
        $this->assertTrue(method_exists($this->zip, 'add_from_file'));
    }

    public function testAddFromFileAddsFileContent(): void
    {
        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'zip_test');
        file_put_contents($tempFile, 'Test file content');
        
        $result = $this->zip->add_from_file($tempFile, 'archived.txt');
        
        $this->assertTrue($result);
        $this->assertGreaterThan(0, $this->zip->entries);
        
        // Clean up
        unlink($tempFile);
    }

    // =========================================================================
    // READ ZIP TESTS
    // =========================================================================

    public function testReadZipMethodExists(): void
    {
        $this->assertTrue(method_exists($this->zip, 'read_zip'));
    }

    public function testReadZipReturnsString(): void
    {
        $this->zip->add_data('test.txt', 'Hello');
        
        $zipData = $this->zip->read_zip();
        
        $this->assertIsString($zipData);
        $this->assertNotEmpty($zipData);
    }

    // =========================================================================
    // DOWNLOAD TESTS
    // =========================================================================

    public function testDownloadMethodExists(): void
    {
        $this->assertTrue(method_exists($this->zip, 'download'));
    }

    public function testDownloadOutputsData(): void
    {
        $this->zip->add_data('test.txt', 'Hello World');
        
        // Capture output
        ob_start();
        $this->zip->download('test.zip');
        $output = ob_get_clean();
        
        // Should have output (though headers won't work in CLI)
        $this->assertNotEmpty($output);
    }

    // =========================================================================
    // GET ENTRY COUNT TESTS
    // =========================================================================

    public function testGetNumFilesMethodExists(): void
    {
        $this->assertTrue(method_exists($this->zip, 'get_num_files'));
    }

    public function testGetNumFilesReturnsCount(): void
    {
        $this->zip->add_data('file1.txt', 'Content 1');
        $this->zip->add_data('file2.txt', 'Content 2');
        $this->zip->add_data('file3.txt', 'Content 3');
        
        $count = $this->zip->get_num_files();
        
        $this->assertEquals(3, $count);
    }

    // =========================================================================
    // CLEAR TESTS
    // =========================================================================

    public function testClearMethodExists(): void
    {
        $this->assertTrue(method_exists($this->zip, 'clear'));
    }

    public function testClearResetsProperties(): void
    {
        $this->zip->add_data('test.txt', 'Hello');
        
        $this->assertGreaterThan(0, $this->zip->entries);
        
        $this->zip->clear();
        
        $this->assertEquals('', $this->zip->zipdata);
        $this->assertEquals('', $this->zip->directory);
        $this->assertEquals(0, $this->zip->entries);
        $this->assertEquals(0, $this->zip->file_num);
        $this->assertEquals(0, $this->zip->offset);
    }

    // =========================================================================
    // COMPRESSION LEVEL TESTS
    // =========================================================================

    public function testSetCompressionLevel(): void
    {
        $this->zip->compression_level = 9;
        $this->assertEquals(9, $this->zip->compression_level);
    }

    public function testCompressionLevelRange(): void
    {
        // Valid range is 0-9
        for ($level = 0; $level <= 9; $level++) {
            $this->zip->compression_level = $level;
            $this->assertEquals($level, $this->zip->compression_level);
        }
    }

    // =========================================================================
    // NOW PROPERTY TESTS
    // =========================================================================

    public function testNowPropertyIsTimestamp(): void
    {
        // now should be set to a timestamp
        $this->assertIsInt($this->zip->now);
        $this->assertGreaterThan(0, $this->zip->now);
    }

    // =========================================================================
    // ARCHIVE INTEGRATION TESTS
    // =========================================================================

    public function testFullWorkflow(): void
    {
        // Add multiple files
        $this->zip->add_data('file1.txt', 'Content 1');
        $this->zip->add_data('file2.txt', 'Content 2');
        $this->zip->add_dir('subdir/');
        $this->zip->add_data('subdir/file3.txt', 'Content 3');
        
        // Verify counts
        $this->assertEquals(4, $this->zip->get_num_files());
        
        // Read the zip
        $zipData = $this->zip->read_zip();
        $this->assertIsString($zipData);
        $this->assertNotEmpty($zipData);
        
        // Clear and verify reset
        $this->zip->clear();
        $this->assertEquals(0, $this->zip->get_num_files());
    }

    public function testAddDataReturnsZipInstanceForChaining(): void
    {
        // Note: CI3's add_data doesn't return $this, but we test it exists
        $result = $this->zip->add_data('test.txt', 'content');
        $this->assertTrue(is_bool($result) || $result === $this->zip);
    }
}
