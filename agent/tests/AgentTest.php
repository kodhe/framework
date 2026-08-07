<?php

declare(strict_types=1);

namespace Kodhe\Agent\Tests;

use Kodhe\Agent\Agent;
use PHPUnit\Framework\TestCase;

/**
 * Agent Test Case
 * 
 * Tests for the main Agent class
 * 
 * @package Kodhe\Agent\Tests
 * @author  Your Name
 * @version 2.0.0
 */
class AgentTest extends TestCase
{
    /**
     * Test browser detection - Chrome
     *
     * @return void
     */
    public function testBrowserDetectionChrome(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        
        $agent = new Agent();
        
        $this->assertEquals('Chrome', $agent->browser());
        $this->assertEquals('120.0.0.0', $agent->version());
        $this->assertTrue($agent->is_browser());
        $this->assertTrue($agent->isBrowser());
    }

    /**
     * Test browser detection - Firefox
     *
     * @return void
     */
    public function testBrowserDetectionFirefox(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0';
        
        $agent = new Agent();
        
        $this->assertEquals('Firefox', $agent->browser());
        $this->assertEquals('121.0', $agent->version());
        $this->assertTrue($agent->is_browser());
    }

    /**
     * Test browser detection - Safari
     *
     * @return void
     */
    public function testBrowserDetectionSafari(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_2) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15';
        
        $agent = new Agent();
        
        $this->assertEquals('Safari', $agent->browser());
        $this->assertEquals('17.2', $agent->version());
        $this->assertTrue($agent->is_browser());
    }

    /**
     * Test mobile detection - iPhone
     *
     * @return void
     */
    public function testMobileDetectionIphone(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1';
        
        $agent = new Agent();
        
        $this->assertTrue($agent->is_mobile());
        $this->assertTrue($agent->isMobile());
        $this->assertEquals('iPhone', $agent->mobile());
    }

    /**
     * Test mobile detection - Android
     *
     * @return void
     */
    public function testMobileDetectionAndroid(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.6099.43 Mobile Safari/537.36';
        
        $agent = new Agent();
        
        $this->assertTrue($agent->is_mobile());
        $this->assertEquals('Android', $agent->mobile());
    }

    /**
     * Test tablet detection - iPad
     *
     * @return void
     */
    public function testTabletDetectionIpad(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPad; CPU OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1';
        
        $agent = new Agent();
        
        $this->assertTrue($agent->is_mobile());
        $this->assertEquals('iPad', $agent->mobile());
    }

    /**
     * Test robot detection - Googlebot
     *
     * @return void
     */
    public function testRobotDetectionGooglebot(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';
        
        $agent = new Agent();
        
        $this->assertTrue($agent->is_robot());
        $this->assertTrue($agent->isRobot());
        $this->assertEquals('Googlebot', $agent->robot());
    }

    /**
     * Test robot detection - Bingbot
     *
     * @return void
     */
    public function testRobotDetectionBingbot(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)';
        
        $agent = new Agent();
        
        $this->assertTrue($agent->is_robot());
        $this->assertEquals('Bingbot', $agent->robot());
    }

    /**
     * Test platform detection - Windows
     *
     * @return void
     */
    public function testPlatformDetectionWindows(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
        
        $agent = new Agent();
        
        $this->assertStringContainsString('Windows', $agent->platform());
    }

    /**
     * Test platform detection - Mac
     *
     * @return void
     */
    public function testPlatformDetectionMac(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_2) AppleWebKit/537.36';
        
        $agent = new Agent();
        
        $this->assertStringContainsString('Mac', $agent->platform());
    }

    /**
     * Test platform detection - Linux
     *
     * @return void
     */
    public function testPlatformDetectionLinux(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36';
        
        $agent = new Agent();
        
        $this->assertStringContainsString('Linux', $agent->platform());
    }

    /**
     * Test custom user agent parsing
     *
     * @return void
     */
    public function testCustomUserAgentParsing(): void
    {
        $agent = new Agent();
        
        $customAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0';
        $agent->parse($customAgent);
        
        $this->assertEquals('Chrome', $agent->browser());
        $this->assertEquals('120.0.0.0', $agent->version());
        $this->assertEquals($customAgent, $agent->agent_string());
    }

    /**
     * Test isDesktop method
     *
     * @return void
     */
    public function testIsDesktop(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0';
        
        $agent = new Agent();
        
        $this->assertTrue($agent->isDesktop());
        $this->assertFalse($agent->is_mobile());
    }

    /**
     * Test accept_lang method
     *
     * @return void
     */
    public function testAcceptLang(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.9,id;q=0.8';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        
        $agent = new Agent();
        
        $this->assertTrue($agent->accept_lang('en'));
        $this->assertTrue($agent->acceptLang('en'));
    }

    /**
     * Test accept_charset method
     *
     * @return void
     */
    public function testAcceptCharset(): void
    {
        $_SERVER['HTTP_ACCEPT_CHARSET'] = 'utf-8,iso-8859-1;q=0.5';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        
        $agent = new Agent();
        
        $this->assertTrue($agent->accept_charset('utf-8'));
        $this->assertTrue($agent->acceptCharset('utf-8'));
    }

    /**
     * Test referrer method
     *
     * @return void
     */
    public function testReferrer(): void
    {
        $_SERVER['HTTP_REFERER'] = 'https://example.com/page';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        
        $agent = new Agent();
        
        $this->assertEquals('https://example.com/page', $agent->referrer());
    }

    /**
     * Test agent_string method
     *
     * @return void
     */
    public function testAgentString(): void
    {
        $testAgent = 'Mozilla/5.0 (Test Agent)';
        $_SERVER['HTTP_USER_AGENT'] = $testAgent;
        
        $agent = new Agent();
        
        $this->assertEquals($testAgent, $agent->agent_string());
        $this->assertEquals($testAgent, $agent->agentString());
    }

    /**
     * Test languages method
     *
     * @return void
     */
    public function testLanguages(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.9';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        
        $agent = new Agent();
        $languages = $agent->languages();
        
        $this->assertIsArray($languages);
        $this->assertContains('en-us', $languages);
        $this->assertContains('en', $languages);
    }

    /**
     * Test charsets method
     *
     * @return void
     */
    public function testCharsets(): void
    {
        $_SERVER['HTTP_ACCEPT_CHARSET'] = 'utf-8,iso-8859-1;q=0.5';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        
        $agent = new Agent();
        $charsets = $agent->charsets();
        
        $this->assertIsArray($charsets);
        $this->assertContains('utf-8', $charsets);
    }
}
