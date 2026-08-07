<?php

declare(strict_types=1);

namespace Kodhe\Framework\View\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\View\AssetManager;

/**
 * @coversDefaultClass \Kodhe\Framework\View\AssetManager
 */
class AssetManagerTest extends TestCase
{
    private AssetManager $assetManager;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->assetManager = new AssetManager([], null);
    }
    
    /**
     * @test
     * @covers ::__construct
     */
    public function asset_manager_initializes_with_empty_assets(): void
    {
        $assets = $this->assetManager->get_assets();
        
        $this->assertIsArray($assets);
        $this->assertArrayHasKey('css', $assets);
        $this->assertArrayHasKey('js', $assets);
        $this->assertArrayHasKey('inline_css', $assets);
        $this->assertArrayHasKey('inline_js', $assets);
        $this->assertArrayHasKey('meta', $assets);
    }
    
    /**
     * @test
     * @covers ::add_css
     * @covers ::render_css
     */
    public function can_add_and_render_css_file(): void
    {
        $this->assetManager->add_css('style.css', 'theme', [], 10);
        
        $rendered = $this->assetManager->render_css('theme');
        
        $this->assertStringContainsString('style.css', $rendered);
        $this->assertStringContainsString('<link', $rendered);
    }
    
    /**
     * @test
     * @covers ::add_js
     * @covers ::render_js
     */
    public function can_add_and_render_js_file(): void
    {
        $this->assetManager->add_js('script.js', 'theme', [], 'footer', 10);
        
        $rendered = $this->assetManager->render_js('footer', 'theme');
        
        $this->assertStringContainsString('script.js', $rendered);
        $this->assertStringContainsString('<script', $rendered);
    }
    
    /**
     * @test
     * @covers ::add_css
     * @covers ::css_exists
     */
    public function prevents_duplicate_css_files(): void
    {
        $this->assetManager->add_css('style.css', 'theme', [], 10);
        $this->assetManager->add_css('style.css', 'theme', [], 10);
        
        $assets = $this->assetManager->get_assets();
        
        // Should only have one CSS file
        $this->assertCount(1, $assets['css']);
    }
    
    /**
     * @test
     * @covers ::add_inline_css
     */
    public function can_add_inline_css(): void
    {
        $this->assetManager->add_inline_css('body { color: red; }', 10);
        
        $rendered = $this->assetManager->render_css();
        
        $this->assertStringContainsString('body { color: red; }', $rendered);
        $this->assertStringContainsString('<style>', $rendered);
    }
    
    /**
     * @test
     * @covers ::add_inline_js
     */
    public function can_add_inline_js(): void
    {
        $this->assetManager->add_inline_js('console.log("test");', 'footer', 10);
        
        $rendered = $this->assetManager->render_js('footer');
        
        $this->assertStringContainsString('console.log("test");', $rendered);
        $this->assertStringContainsString('<script>', $rendered);
    }
    
    /**
     * @test
     * @covers ::meta
     * @covers ::render_meta
     */
    public function can_add_and_render_meta_tags(): void
    {
        $this->assetManager->meta('description', 'Test description');
        
        $rendered = $this->assetManager->render_meta();
        
        $this->assertStringContainsString('description', $rendered);
        $this->assertStringContainsString('Test description', $rendered);
    }
    
    /**
     * @test
     * @covers ::clear_assets
     */
    public function can_clear_all_assets(): void
    {
        $this->assetManager->add_css('style.css');
        $this->assetManager->add_js('script.js');
        
        $this->assetManager->clear_assets();
        
        $assets = $this->assetManager->get_assets();
        
        $this->assertEmpty($assets['css']);
        $this->assertEmpty($assets['js']);
    }
    
    /**
     * @test
     * @covers ::clear_assets
     */
    public function can_clear_specific_asset_type(): void
    {
        $this->assetManager->add_css('style.css');
        $this->assetManager->add_js('script.js');
        
        $this->assetManager->clear_assets('css');
        
        $assets = $this->assetManager->get_assets();
        
        $this->assertEmpty($assets['css']);
        $this->assertNotEmpty($assets['js']);
    }
    
    /**
     * @test
     * @covers ::group
     */
    public function can_set_current_group(): void
    {
        $result = $this->assetManager->group('admin');
        
        $this->assertSame($this->assetManager, $result);
    }
    
    /**
     * @test
     * @covers ::is_external
     */
    public function detects_external_urls(): void
    {
        $reflection = new \ReflectionClass($this->assetManager);
        $method = $reflection->getMethod('is_external');
        $method->setAccessible(true);
        
        $this->assertTrue($method->invoke($this->assetManager, 'https://cdn.example.com/style.css'));
        $this->assertFalse($method->invoke($this->assetManager, 'css/style.css'));
    }
}
