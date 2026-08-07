<?php

namespace Kodhe\Typography\Tests;

use Kodhe\Typography\Typography;
use Kodhe\Typography\Factory\TypographyFactory;
use PHPUnit\Framework\TestCase;

/**
 * Test cases untuk Typography library.
 */
class TypographyTest extends TestCase
{
    private Typography $typography;

    protected function setUp(): void
    {
        $this->typography = TypographyFactory::make();
    }

    public function testAutoTypographyBasic(): void
    {
        $input = "Hello world.\n\nThis is a test.";
        $output = $this->typography->auto_typography($input);

        $this->assertStringContainsString('<p>', $output);
        $this->assertStringContainsString('</p>', $output);
    }

    public function testSmartQuotesDouble(): void
    {
        $input = 'He said "Hello World" to me.';
        $output = $this->typography->format_characters($input);

        $this->assertStringContainsString('&ldquo;', $output);
        $this->assertStringContainsString('&rdquo;', $output);
    }

    public function testSmartQuotesSingle(): void
    {
        $input = "It's John's book.";
        $output = $this->typography->format_characters($input);

        $this->assertStringContainsString('&rsquo;', $output);
    }

    public function testCharacterFormattingEmDash(): void
    {
        $input = 'Test -- this is a dash';
        $output = $this->typography->format_characters($input);

        $this->assertStringContainsString('&#8212;', $output);
    }

    public function testCharacterFormattingEllipsis(): void
    {
        $input = 'Wait...';
        $output = $this->typography->format_characters($input);

        $this->assertStringContainsString('&#8230;', $output);
    }

    public function testNl2brExceptPre(): void
    {
        $input = "Line 1\nLine 2<pre>\nLine 3\n</pre>\nLine 4";
        $output = $this->typography->nl2br_except_pre($input);

        $this->assertStringContainsString("<br />\n", $output);
        $this->assertStringContainsString('<pre>', $output);
    }

    public function testHtmlPreservation(): void
    {
        $input = '<p>Hello <strong>world</strong></p>';
        $output = $this->typography->auto_typography($input);

        $this->assertStringContainsString('<strong>world</strong>', $output);
    }

    public function testProtectBracedQuotes(): void
    {
        $input = '{if $name == "John"}';
        $output = $this->typography->protect_braced_quotes($input);

        $this->assertEquals($input, $output);
    }

    public function testSetDelimiters(): void
    {
        $this->typography->set_delimiters('{{', '}}');
        
        $this->assertEquals('{{', $this->typography->get_left_delimiter());
        $this->assertEquals('}}', $this->typography->get_right_delimiter());
    }

    public function testFactoryMakeWithConfig(): void
    {
        $typography = TypographyFactory::makeWithConfig([
            'reduce_linebreaks' => true
        ]);

        $this->assertInstanceOf(Typography::class, $typography);
    }

    public function testEmptyString(): void
    {
        $input = '';
        $output = $this->typography->auto_typography($input);

        $this->assertEquals('', $output);
    }

    public function testNestedStructures(): void
    {
        $input = "<div>\n<p>Test \"quotes\"</p>\n</div>";
        $output = $this->typography->auto_typography($input);

        $this->assertStringContainsString('<div>', $output);
        $this->assertStringContainsString('&ldquo;', $output);
    }

    public function testCopyrightSymbol(): void
    {
        $input = 'Copyright (c) 2024';
        $output = $this->typography->format_characters($input);

        $this->assertStringContainsString('&#169;', $output);
    }

    public function testTrademarkSymbol(): void
    {
        $input = 'Product (tm)';
        $output = $this->typography->format_characters($input);

        $this->assertStringContainsString('&#8482;', $output);
    }

    public function testReduceLinebreaks(): void
    {
        $input = "Line 1\n\n\n\nLine 2";
        $output = $this->typography->auto_typography($input, true);

        $this->assertStringContainsString('<p>', $output);
    }

    public function testBackwardCompatibility(): void
    {
        // Pastikan API CI3 tetap berfungsi
        $this->assertTrue(method_exists($this->typography, 'auto_typography'));
        $this->assertTrue(method_exists($this->typography, 'format_characters'));
        $this->assertTrue(method_exists($this->typography, 'nl2br_except_pre'));
        $this->assertTrue(method_exists($this->typography, 'protect_braced_quotes'));
        $this->assertTrue(method_exists($this->typography, 'set_delimiters'));
    }
}
