<?php

declare(strict_types=1);

namespace Kodhe\Framework\Tests\CI3Compat;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Upload\Upload;

/**
 * Test Upload library compatibility with CodeIgniter 3 API
 */
class UploadTest extends TestCase
{
    private Upload $upload;

    protected function setUp(): void
    {
        parent::setUp();
        $this->upload = new Upload();
    }

    // =========================================================================
    // DEFAULT PROPERTIES TESTS
    // =========================================================================

    public function testDefaultMaxSize(): void
    {
        $this->assertEquals(0, $this->upload->max_size);
    }

    public function testDefaultMaxWidth(): void
    {
        $this->assertEquals(0, $this->upload->max_width);
    }

    public function testDefaultMaxHeight(): void
    {
        $this->assertEquals(0, $this->upload->max_height);
    }

    public function testDefaultMinWidth(): void
    {
        $this->assertEquals(0, $this->upload->min_width);
    }

    public function testDefaultMinHeight(): void
    {
        $this->assertEquals(0, $this->upload->min_height);
    }

    public function testDefaultMaxFilename(): void
    {
        $this->assertEquals(0, $this->upload->max_filename);
    }

    public function testDefaultMaxFilenameIncrement(): void
    {
        $this->assertEquals(100, $this->upload->max_filename_increment);
    }

    public function testDefaultAllowedTypes(): void
    {
        $this->assertEquals('', $this->upload->allowed_types);
    }

    public function testDefaultFileName(): void
    {
        $this->assertEquals('', $this->upload->file_name);
    }

    public function testDefaultOrigName(): void
    {
        $this->assertEquals('', $this->upload->orig_name);
    }

    public function testDefaultFileType(): void
    {
        $this->assertEquals('', $this->upload->file_type);
    }

    public function testDefaultFileExt(): void
    {
        $this->assertEquals('', $this->upload->file_ext);
    }

    public function testDefaultFileExtToLower(): void
    {
        $this->assertFalse($this->upload->file_ext_tolower);
    }

    public function testDefaultUploadPath(): void
    {
        $this->assertEquals('', $this->upload->upload_path);
    }

    public function testDefaultOverwrite(): void
    {
        $this->assertFalse($this->upload->overwrite);
    }

    public function testDefaultEncryptName(): void
    {
        $this->assertFalse($this->upload->encrypt_name);
    }

    public function testDefaultIsImage(): void
    {
        $this->assertFalse($this->upload->is_image);
    }

    public function testDefaultRemoveSpaces(): void
    {
        $this->assertTrue($this->upload->remove_spaces);
    }

    public function testDefaultDetectMime(): void
    {
        $this->assertTrue($this->upload->detect_mime);
    }

    public function testDefaultXssClean(): void
    {
        $this->assertFalse($this->upload->xss_clean);
    }

    public function testDefaultModMimeFix(): void
    {
        $this->assertTrue($this->upload->mod_mime_fix);
    }

    // =========================================================================
    // INITIALIZATION TESTS
    // =========================================================================

    public function testInitializeWithConfigArray(): void
    {
        $config = [
            'upload_path' => './uploads/',
            'allowed_types' => 'gif|jpg|png',
            'max_size' => 1024,
            'max_width' => 800,
            'max_height' => 600,
            'encrypt_name' => true,
            'overwrite' => false,
            'remove_spaces' => true,
        ];

        $upload = new Upload($config);

        $this->assertEquals('./uploads/', $upload->upload_path);
        $this->assertEquals('gif|jpg|png', $upload->allowed_types);
        $this->assertEquals(1024, $upload->max_size);
        $this->assertEquals(800, $upload->max_width);
        $this->assertEquals(600, $upload->max_height);
        $this->assertTrue($upload->encrypt_name);
        $this->assertFalse($upload->overwrite);
        $this->assertTrue($upload->remove_spaces);
    }

    public function testInitializeMethodReturnsSelf(): void
    {
        $result = $this->upload->initialize([]);
        $this->assertSame($this->upload, $result);
    }

    // =========================================================================
    // DO UPLOAD TESTS
    // =========================================================================

    public function testDoUploadMethodExists(): void
    {
        $this->assertTrue(method_exists($this->upload, 'do_upload'));
    }

    public function testDoUploadWithoutFieldDefaultsToUpload(): void
    {
        // This will fail without actual file, but tests the method exists and signature
        $this->upload->initialize(['upload_path' => sys_get_temp_dir()]);
        
        // Method should exist and accept optional parameter
        $reflection = new \ReflectionMethod($this->upload, 'do_upload');
        $params = $reflection->getParameters();
        
        $this->assertCount(1, $params);
        $this->assertEquals('field', $params[0]->getName());
        $this->assertTrue($params[0]->isOptional());
    }

    // =========================================================================
    // IS UPLOADED TESTS
    // =========================================================================

    public function testIsUploadedMethodExists(): void
    {
        $this->assertTrue(method_exists($this->upload, 'is_uploaded_file'));
    }

    // =========================================================================
    // DATA METHOD TESTS
    // =========================================================================

    public function testDataMethodExists(): void
    {
        $this->assertTrue(method_exists($this->upload, 'data'));
    }

    // =========================================================================
    // ERROR HANDLING TESTS
    // =========================================================================

    public function testDisplayErrorMethodExists(): void
    {
        $this->assertTrue(method_exists($this->upload, 'display_errors'));
    }

    public function testSetErrorMethodExists(): void
    {
        $this->assertTrue(method_exists($this->upload, 'set_error'));
    }

    public function testGetErrorMessageMethodExists(): void
    {
        $this->assertTrue(method_exists($this->upload, 'get_error_message'));
    }

    // =========================================================================
    // FILE SIZE CONVERSION TESTS
    // =========================================================================

    public function testParseSizeUnitsMethodExists(): void
    {
        $this->assertTrue(method_exists($this->upload, 'parse_size_units'));
    }

    // =========================================================================
    // MIME TYPE TESTS
    // =========================================================================

    public function testMimeTypeMethodExists(): void
    {
        $this->assertTrue(method_exists($this->upload, 'mimes'));
    }

    // =========================================================================
    // IMAGE PROPERTIES TESTS
    // =========================================================================

    public function testGetImagePropertiesMethodExists(): void
    {
        $this->assertTrue(method_exists($this->upload, 'get_image_properties'));
    }

    // =========================================================================
    // FILENAME CLEANING TESTS
    // =========================================================================

    public function testCleanFileNameMethodExists(): void
    {
        $this->assertTrue(method_exists($this->upload, 'clean_file_name'));
    }

    public function testCleanFileNameRemovesSpecialChars(): void
    {
        $dirtyName = 'test@#$%file name.txt';
        $cleanName = $this->upload->clean_file_name($dirtyName);
        
        $this->assertNotEquals($dirtyName, $cleanName);
        $this->assertStringContainsString('test', $cleanName);
        $this->assertStringContainsString('file', $cleanName);
        $this->assertStringContainsString('.txt', $cleanName);
    }

    // =========================================================================
    // LIMIT FILE NAME LENGTH TESTS
    // =========================================================================

    public function testLimitFilenameLengthMethodExists(): void
    {
        $this->assertTrue(method_exists($this->upload, 'limit_filename_length'));
    }

    // =========================================================================
    // VALIDATE MIME TYPE TESTS
    // =========================================================================

    public function testValidateMimeTypeMethodExists(): void
    {
        $this->assertTrue(method_exists($this->upload, '_validate_mime'));
    }

    // =========================================================================
    // PREPARE FILENAME STRATEGY TESTS
    // =========================================================================

    public function testPrepareFilenameUsesEncryptNameWhenEnabled(): void
    {
        $this->upload->initialize([
            'encrypt_name' => true,
            'upload_path' => sys_get_temp_dir(),
        ]);
        
        // When encrypt_name is true, filename should be encrypted (MD5)
        $method = new \ReflectionMethod($this->upload, '_prepare_filename');
        $method->setAccessible(true);
        
        $filename = $method->invoke($this->upload, 'test.txt');
        
        // Encrypted filename should be different from original
        $this->assertNotEquals('test.txt', $filename);
        // Should still have .txt extension
        $this->assertStringEndsWith('.txt', $filename);
    }

    public function testPrepareFilenameUsesOriginalWhenEncryptDisabled(): void
    {
        $this->upload->initialize([
            'encrypt_name' => false,
            'remove_spaces' => true,
            'upload_path' => sys_get_temp_dir(),
        ]);
        
        $method = new \ReflectionMethod($this->upload, '_prepare_filename');
        $method->setAccessible(true);
        
        $filename = $method->invoke($this->upload, 'my document.pdf');
        
        // Should remove spaces when remove_spaces is true
        $this->assertStringContainsString('my', $filename);
        $this->assertStringContainsString('document.pdf', $filename);
    }

    // =========================================================================
    // GET CI INSTANCE TESTS
    // =========================================================================

    public function testGetCIMethodExists(): void
    {
        $this->assertTrue(method_exists($this->upload, 'getCI'));
    }

    // =========================================================================
    // LOG MESSAGE TESTS
    // =========================================================================

    public function testLogMessageMethodExists(): void
    {
        $this->assertTrue(method_exists($this->upload, 'logMessage'));
    }

    // =========================================================================
    // GET MIMES TESTS
    // =========================================================================

    public function testGetMimesReturnsArray(): void
    {
        $method = new \ReflectionMethod($this->upload, 'getMimes');
        $method->setAccessible(true);
        
        $mimes = $method->invoke($this->upload);
        
        $this->assertIsArray($mimes);
        $this->assertNotEmpty($mimes);
    }
}
