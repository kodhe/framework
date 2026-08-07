<?php

declare(strict_types=1);

namespace Kodhe\Framework\View\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\View\Engine\EngineFactory;

/**
 * @coversDefaultClass \Kodhe\Framework\View\Engine\EngineFactory
 */
class EngineFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        EngineFactory::clear();
    }
    
    /**
     * @test
     * @covers ::register
     * @covers ::make
     */
    public function can_register_and_create_engine(): void
    {
        EngineFactory::register('test', \Kodhe\Framework\View\Engine\PhpEngine::class, []);
        
        $engine = EngineFactory::make('test');
        
        $this->assertInstanceOf(\Kodhe\Framework\View\Engine\PhpEngine::class, $engine);
    }
    
    /**
     * @test
     * @covers ::setDefault
     * @covers ::make
     */
    public function uses_default_engine_when_none_specified(): void
    {
        EngineFactory::register('default', \Kodhe\Framework\View\Engine\PhpEngine::class, []);
        EngineFactory::setDefault('default');
        
        $engine = EngineFactory::make();
        
        $this->assertInstanceOf(\Kodhe\Framework\View\Engine\PhpEngine::class, $engine);
    }
    
    /**
     * @test
     * @covers ::hasEngine
     */
    public function has_engine_returns_true_for_registered_engine(): void
    {
        EngineFactory::register('custom', \Kodhe\Framework\View\Engine\PhpEngine::class, []);
        
        $this->assertTrue(EngineFactory::hasEngine('custom'));
    }
    
    /**
     * @test
     * @covers ::hasEngine
     */
    public function has_engine_returns_false_for_unregistered_engine(): void
    {
        $this->assertFalse(EngineFactory::hasEngine('nonexistent'));
    }
    
    /**
     * @test
     * @covers ::getEngines
     */
    public function get_engines_returns_all_registered_engines(): void
    {
        EngineFactory::register('engine1', \Kodhe\Framework\View\Engine\PhpEngine::class, []);
        EngineFactory::register('engine2', \Kodhe\Framework\View\Engine\PhpEngine::class, []);
        
        $engines = EngineFactory::getEngines();
        
        $this->assertContains('engine1', $engines);
        $this->assertContains('engine2', $engines);
    }
    
    /**
     * @test
     * @covers ::unregister
     */
    public function can_unregister_engine(): void
    {
        EngineFactory::register('temp', \Kodhe\Framework\View\Engine\PhpEngine::class, []);
        $this->assertTrue(EngineFactory::hasEngine('temp'));
        
        EngineFactory::unregister('temp');
        
        $this->assertFalse(EngineFactory::hasEngine('temp'));
    }
    
    /**
     * @test
     * @covers ::clear
     */
    public function clear_removes_all_registered_engines(): void
    {
        EngineFactory::register('engine1', \Kodhe\Framework\View\Engine\PhpEngine::class, []);
        EngineFactory::register('engine2', \Kodhe\Framework\View\Engine\PhpEngine::class, []);
        
        EngineFactory::clear();
        
        $this->assertEmpty(EngineFactory::getEngines());
    }
}
