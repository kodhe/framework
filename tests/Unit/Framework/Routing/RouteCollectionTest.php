<?php declare(strict_types=1);

namespace Kodhe\Tests\Unit\Framework\Routing;

use Kodhe\Framework\Routing\RouteCollection;
use Kodhe\Framework\Routing\Route;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the RouteCollection class
 */
class RouteCollectionTest extends TestCase
{
    private RouteCollection $collection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = new RouteCollection();
    }

    public function testRouteCollectionInstantiation(): void
    {
        $this->assertInstanceOf(RouteCollection::class, $this->collection);
    }

    public function testAddRouteAddsToCollection(): void
    {
        $route = new Route('GET', '/test', 'HomeController@index');
        
        $this->collection->add($route);
        
        $routes = $this->collection->all();
        
        $this->assertCount(1, $routes);
        $this->assertContains($route, $routes);
    }

    public function testGetRoutesByMethod(): void
    {
        $getRoute = new Route('GET', '/test', 'HomeController@index');
        $postRoute = new Route('POST', '/test', 'HomeController@store');
        
        $this->collection->add($getRoute);
        $this->collection->add($postRoute);
        
        $getRoutes = $this->collection->getByMethod('GET');
        
        $this->assertCount(1, $getRoutes);
        $this->assertContains($getRoute, $getRoutes);
    }

    public function testFindRouteByUriAndMethod(): void
    {
        $route = new Route('GET', '/users/{id}', 'UserController@show');
        
        $this->collection->add($route);
        
        $found = $this->collection->find('GET', '/users/123');
        
        $this->assertNotNull($found);
        $this->assertSame($route, $found['route']);
        $this->assertEquals(['id' => '123'], $found['params']);
    }

    public function testFindReturnsNullForNonExistentRoute(): void
    {
        $route = new Route('GET', '/users', 'UserController@index');
        $this->collection->add($route);
        
        $found = $this->collection->find('POST', '/users');
        
        $this->assertNull($found);
    }

    public function testGroupMiddlewareAppliedToRoutes(): void
    {
        $this->collection->group(['middleware' => ['auth']], function($router) {
            $router->get('/protected', 'ProtectedController@index');
        });
        
        $found = $this->collection->find('GET', '/protected');
        
        $this->assertNotNull($found);
        $this->assertContains('auth', $found['route']->getMiddleware());
    }

    public function testPrefixAppliedToGroupedRoutes(): void
    {
        $this->collection->group(['prefix' => '/api'], function($router) {
            $router->get('/users', 'UserController@index');
        });
        
        $found = $this->collection->find('GET', '/api/users');
        
        $this->assertNotNull($found);
    }

    public function testNamespaceAppliedToGroupedRoutes(): void
    {
        $this->collection->group(['namespace' => 'App\\Http\\Controllers'], function($router) {
            $router->get('/users', 'UserController@index');
        });
        
        $found = $this->collection->find('GET', '/users');
        
        $this->assertNotNull($found);
        $this->assertStringStartsWith('App\\Http\\Controllers\\', $found['route']->getHandler());
    }

    public function testClearRemovesAllRoutes(): void
    {
        $this->collection->add(new Route('GET', '/one', 'Controller@one'));
        $this->collection->add(new Route('GET', '/two', 'Controller@two'));
        
        $this->collection->clear();
        
        $this->assertCount(0, $this->collection->all());
    }

    public function testCountReturnsNumberOfRoutes(): void
    {
        $this->collection->add(new Route('GET', '/one', 'Controller@one'));
        $this->collection->add(new Route('GET', '/two', 'Controller@two'));
        $this->collection->add(new Route('POST', '/three', 'Controller@three'));
        
        $this->assertCount(3, $this->collection);
    }

    public function testIteratorAllowsForeachLoop(): void
    {
        $route1 = new Route('GET', '/one', 'Controller@one');
        $route2 = new Route('GET', '/two', 'Controller@two');
        
        $this->collection->add($route1);
        $this->collection->add($route2);
        
        $count = 0;
        foreach ($this->collection as $route) {
            $count++;
            $this->assertInstanceOf(Route::class, $route);
        }
        
        $this->assertEquals(2, $count);
    }

    public function testWhereAddsConstraintToRoute(): void
    {
        $this->collection->get('/users/{id}', 'UserController@show')
            ->where(['id' => '[0-9]+']);
        
        $found = $this->collection->find('GET', '/users/123');
        $this->assertNotNull($found);
        
        $notFound = $this->collection->find('GET', '/users/abc');
        $this->assertNull($notFound);
    }

    public function testNamedRoutesCanBeRetrievedByName(): void
    {
        $route = new Route('GET', '/profile', 'UserController@profile');
        $route->name('user.profile');
        
        $this->collection->add($route);
        
        $namedRoute = $this->collection->getByName('user.profile');
        
        $this->assertNotNull($namedRoute);
        $this->assertSame($route, $namedRoute);
    }
}
