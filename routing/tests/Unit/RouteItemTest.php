<?php

declare(strict_types=1);

namespace Kodhe\Framework\Routing\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Routing\Core\RouteItem;

/**
 * Unit tests for RouteItem class
 */
class RouteItemTest extends TestCase
{
    public function test_constructor_creates_route_with_basic_properties(): void
    {
        $route = new RouteItem('GET', '/users', 'UserController@index');
        
        $this->assertEquals('GET', $route->getMethod());
        $this->assertEquals('/users', $route->getUri());
        $this->assertEquals('UserController@index', $route->getAction());
    }

    public function test_method_is_normalized_to_uppercase(): void
    {
        $route = new RouteItem('get', '/test', 'TestController');
        
        $this->assertEquals('GET', $route->getMethod());
    }

    public function test_name_can_be_set_and_retrieved(): void
    {
        $route = new RouteItem('GET', '/users', 'UserController@index');
        $route->name('users.index');
        
        $this->assertEquals('users.index', $route->getName());
    }

    public function test_middleware_can_be_added_as_array(): void
    {
        $route = new RouteItem('GET', '/users', 'UserController@index');
        $route->middleware(['auth', 'admin']);
        
        $this->assertEquals(['auth', 'admin'], $route->getMiddleware());
    }

    public function test_middleware_can_be_added_as_variadic(): void
    {
        $route = new RouteItem('GET', '/users', 'UserController@index');
        $route->middleware('auth', 'admin');
        
        $this->assertEquals(['auth', 'admin'], $route->getMiddleware());
    }

    public function test_middleware_can_be_chained(): void
    {
        $route = new RouteItem('GET', '/users', 'UserController@index');
        $route->middleware('auth')->middleware('admin');
        
        $this->assertEquals(['auth', 'admin'], $route->getMiddleware());
    }

    public function test_matches_exact_uri(): void
    {
        $route = new RouteItem('GET', '/users', 'UserController@index');
        
        $this->assertTrue($route->matches('/users'));
        $this->assertFalse($route->matches('/posts'));
    }

    public function test_matches_uri_with_trailing_slash(): void
    {
        $route = new RouteItem('GET', '/users', 'UserController@index');
        
        $this->assertTrue($route->matches('/users'));
        $this->assertTrue($route->matches('/users/'));
    }

    public function test_matches_uri_with_parameters(): void
    {
        $route = new RouteItem('GET', '/users/{id}', 'UserController@show');
        
        $this->assertTrue($route->matches('/users/123'));
        $this->assertEquals(['id' => '123'], $route->getParameters());
    }

    public function test_matches_uri_with_multiple_parameters(): void
    {
        $route = new RouteItem('GET', '/users/{userId}/posts/{postId}', 'PostController@show');
        
        $this->assertTrue($route->matches('/users/456/posts/789'));
        $params = $route->getParameters();
        
        $this->assertEquals('456', $params['userId']);
        $this->assertEquals('789', $params['postId']);
    }

    public function test_where_adds_parameter_constraint(): void
    {
        $route = new RouteItem('GET', '/users/{id}', 'UserController@show');
        $route->where('id', '[0-9]+');
        
        $this->assertTrue($route->matches('/users/123'));
        $this->assertFalse($route->matches('/users/abc'));
    }

    public function test_url_generates_with_parameters(): void
    {
        $route = new RouteItem('GET', '/users/{id}', 'UserController@show');
        
        $url = $route->url(['id' => '123']);
        
        $this->assertEquals('/users/123', $url);
    }

    public function test_url_removes_optional_parameters(): void
    {
        $route = new RouteItem('GET', '/users/{id?}', 'UserController@show');
        
        $url = $route->url([]);
        
        $this->assertEquals('/users', $url);
    }

    public function test_namespace_can_be_set(): void
    {
        $route = new RouteItem('GET', '/users', 'UserController@index');
        $route->setNamespace('App\\Controllers\\');
        
        $this->assertEquals('App\\Controllers\\', $route->getNamespace());
    }
}
