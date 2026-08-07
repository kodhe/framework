<?php

declare(strict_types=1);

namespace Kodhe\Framework\View\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\View\Engine\PhpEngine;

/**
 * @coversDefaultClass \Kodhe\Framework\View\Engine\PhpEngine
 */
class PhpEngineTest extends TestCase
{
    private PhpEngine $engine;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new PhpEngine(['views_path' => VIEWPATH]);
    }
    
    /**
     * @test
     * @covers ::__construct
     * @covers ::getExtension
     */
    public function engine_has_php_extension(): void
    {
        $this->assertEquals('.php', $this->engine->getExtension());
    }
    
    /**
     * @test
     * @covers ::setPath
     * @covers ::getPath
     */
    public function can_set_and_get_views_path(): void
    {
        $customPath = '/custom/views/';
        $this->engine->setPath($customPath);
        
        $this->assertEquals(rtrim($customPath, '/') . '/', $this->engine->getPath());
    }
    
    /**
     * @test
     * @covers ::exists
     */
    public function exists_returns_false_for_nonexistent_view(): void
    {
        $this->assertFalse($this->engine->exists('nonexistent_view'));
    }
}
