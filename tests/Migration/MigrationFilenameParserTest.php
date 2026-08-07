<?php

namespace Kodhe\Migration\Tests;

use Kodhe\Migration\Parser\MigrationFilenameParser;
use Kodhe\Migration\Exceptions\InvalidMigrationFileException;
use PHPUnit\Framework\TestCase;

/**
 * Test case untuk MigrationFilenameParser
 *
 * @package Kodhe\Migration\Tests
 */
class MigrationFilenameParserTest extends TestCase
{
    private MigrationFilenameParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new MigrationFilenameParser();
    }

    public function testParseValidFilename(): void
    {
        $result = $this->parser->parse('1234567890_create_users.php');
        
        $this->assertEquals(1234567890, $result['version']);
        $this->assertEquals('create_users', $result['name']);
    }

    public function testParseFilenameWithUnderscoresInName(): void
    {
        $result = $this->parser->parse('123_add_foreign_key_to_posts.php');
        
        $this->assertEquals(123, $result['version']);
        $this->assertEquals('foreign_key_to_posts', $result['name']);
    }

    public function testParseInvalidFilenameThrowsException(): void
    {
        $this->expectException(InvalidMigrationFileException::class);
        $this->parser->parse('invalid_filename.php');
    }

    public function testParseFilenameWithoutExtension(): void
    {
        $this->expectException(InvalidMigrationFileException::class);
        $this->parser->parse('123_create_users');
    }

    public function testParseFilenameWithNonNumericPrefix(): void
    {
        $this->expectException(InvalidMigrationFileException::class);
        $this->parser->parse('abc_create_users.php');
    }

    public function testGetVersionReturnsCorrectVersion(): void
    {
        $version = $this->parser->getVersion('999_test_migration.php');
        
        $this->assertEquals(999, $version);
    }

    public function testGetVersionReturnsNullForInvalidFilename(): void
    {
        $version = $this->parser->getVersion('invalid.php');
        
        $this->assertNull($version);
    }

    public function testIsValidReturnsTrueForValidFilename(): void
    {
        $this->assertTrue($this->parser->isValid('123456_valid_name.php'));
    }

    public function testIsValidReturnsFalseForInvalidFilename(): void
    {
        $this->assertFalse($this->parser->isValid('invalid.php'));
        $this->assertFalse($this->parser->isValid('abc_name.php'));
        $this->assertFalse($this->parser->isValid('123.php'));
    }

    public function testParseZeroVersion(): void
    {
        $result = $this->parser->parse('0_initial_setup.php');
        
        $this->assertEquals(0, $result['version']);
        $this->assertEquals('initial_setup', $result['name']);
    }

    public function testParseLargeVersionNumber(): void
    {
        $result = $this->parser->parse('9999999999_future_migration.php');
        
        $this->assertEquals(9999999999, $result['version']);
        $this->assertEquals('future_migration', $result['name']);
    }
}
