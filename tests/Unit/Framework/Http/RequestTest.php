<?php declare(strict_types=1);

namespace Kodhe\Tests\Unit\Framework\Http;

use Kodhe\Framework\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Request class
 */
class RequestTest extends TestCase
{
    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->request = new Request(
            ['get_param' => 'get_value'],
            ['post_param' => 'post_value'],
            ['cookie_param' => 'cookie_value'],
            [],
            ['REQUEST_METHOD' => 'GET', 'HTTP_HOST' => 'example.com']
        );
    }

    public function testRequestInstantiation(): void
    {
        $this->assertInstanceOf(Request::class, $this->request);
    }

    public function testGetMethodReturnsGetParameters(): void
    {
        $value = $this->request->get('get_param');
        
        $this->assertSame('get_value', $value);
    }

    public function testPostMethodReturnsPostParameters(): void
    {
        $value = $this->request->post('post_param');
        
        $this->assertSame('post_value', $value);
    }

    public function testCookieMethodReturnsCookieValue(): void
    {
        $value = $this->request->cookie('cookie_param');
        
        $this->assertSame('cookie_value', $value);
    }

    public function testGetMethodReturnsNullForNonExistentParam(): void
    {
        $value = $this->request->get('nonexistent');
        
        $this->assertNull($value);
    }

    public function testGetMethodReturnsDefaultValue(): void
    {
        $defaultValue = 'default';
        $value = $this->request->get('nonexistent', $defaultValue);
        
        $this->assertSame($defaultValue, $value);
    }

    public function testIsGetMethodReturnsTrueForGetRequests(): void
    {
        $request = new Request([], [], [], [], ['REQUEST_METHOD' => 'GET']);
        
        $this->assertTrue($request->isGet());
    }

    public function testIsPostMethodReturnsTrueForPostRequests(): void
    {
        $request = new Request([], [], [], [], ['REQUEST_METHOD' => 'POST']);
        
        $this->assertTrue($request->isPost());
    }

    public function testServerMethodReturnsServerVariable(): void
    {
        $value = $this->request->server('HTTP_HOST');
        
        $this->assertSame('example.com', $value);
    }

    public function testServerMethodReturnsDefaultValue(): void
    {
        $defaultValue = 'default_host';
        $value = $this->request->server('NONEXISTENT', $defaultValue);
        
        $this->assertSame($defaultValue, $value);
    }

    public function testHasMethodReturnsTrueForExistingGetParam(): void
    {
        $this->assertTrue($this->request->has('get_param'));
    }

    public function testHasMethodReturnsFalseForNonExistentParam(): void
    {
        $this->assertFalse($this->request->has('nonexistent'));
    }

    public function testOnlyReturnsSpecifiedKeys(): void
    {
        $request = new Request(
            ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'],
            [],
            [],
            [],
            []
        );
        
        $result = $request->only(['key1', 'key3']);
        
        $this->assertEquals([
            'key1' => 'value1',
            'key3' => 'value3'
        ], $result);
    }

    public function testExceptReturnsAllExceptSpecifiedKeys(): void
    {
        $request = new Request(
            ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'],
            [],
            [],
            [],
            []
        );
        
        $result = $request->except(['key2']);
        
        $this->assertEquals([
            'key1' => 'value1',
            'key3' => 'value3'
        ], $result);
    }

    public function testInputMergesGetAndPost(): void
    {
        $request = new Request(
            ['get_key' => 'get_value'],
            ['post_key' => 'post_value'],
            [],
            [],
            []
        );
        
        $input = $request->input();
        
        $this->assertArrayHasKey('get_key', $input);
        $this->assertArrayHasKey('post_key', $input);
        $this->assertSame('get_value', $input['get_key']);
        $this->assertSame('post_value', $input['post_key']);
    }

    public function testPathReturnsUriPath(): void
    {
        $request = new Request([], [], [], [], ['REQUEST_URI' => '/users/123?query=test']);
        
        $path = $request->path();
        
        $this->assertStringContainsString('/users/123', $path);
    }

    public function testFullUrlReturnsCompleteUrl(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [
                'REQUEST_METHOD' => 'GET',
                'HTTP_HOST' => 'example.com',
                'REQUEST_URI' => '/test?param=value',
                'HTTPS' => 'on'
            ]
        );
        
        $url = $request->fullUrl();
        
        $this->assertStringContainsString('https://example.com', $url);
        $this->assertStringContainsString('/test', $url);
    }

    public function testAjaxDetectionWithXmlHttpRequest(): void
    {
        $request = new Request([], [], [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        
        $this->assertTrue($request->isAjax());
    }

    public function testJsonMethodReturnsParsedJsonBody(): void
    {
        $request = Request::fromJsonString('{"name": "John", "age": 30}');
        
        $json = $request->json();
        
        $this->assertNotNull($json);
        $this->assertSame('John', $json->get('name'));
        $this->assertSame(30, $json->get('age'));
    }

    public function testIpReturnsClientIpAddress(): void
    {
        $request = new Request([], [], [], [], ['REMOTE_ADDR' => '192.168.1.1']);
        
        $ip = $request->ip();
        
        $this->assertSame('192.168.1.1', $ip);
    }

    public function testUserAgentReturnsBrowserInfo(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';
        $request = new Request([], [], [], [], ['HTTP_USER_AGENT' => $userAgent]);
        
        $this->assertSame($userAgent, $request->userAgent());
    }

    public function testReferrerReturnsReferrerUrl(): void
    {
        $referrer = 'https://google.com';
        $request = new Request([], [], [], [], ['HTTP_REFERER' => $referrer]);
        
        $this->assertSame($referrer, $request->referrer());
    }

    public function testSecureMethodDetectsHttps(): void
    {
        $request = new Request([], [], [], [], ['HTTPS' => 'on']);
        
        $this->assertTrue($request->secure());
    }
}
