<?php

namespace Tests\Unit\Core;

use App\Core\Router;
use App\Core\Request;
use App\Core\Response;
use App\Core\Container;
use App\Middleware\AuthMiddleware;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    private $container;
    private $authMiddleware;
    private $router;
    
    protected function setUp(): void
    {
        $this->container = $this->createMock(Container::class);
        $this->authMiddleware = $this->createMock(AuthMiddleware::class);
        $this->router = new Router($this->container, $this->authMiddleware);
    }
    
    public function testRouteNotFound()
    {
        $request = new Request('/nonexistent', 'GET');
        $response = $this->router->dispatch($request);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(404, $this->getResponseProperty($response, 'statusCode'));
    }
    
    public function testRootPathReturnsWelcomeMessage()
    {
        $request = new Request('/', 'GET');
        $response = $this->router->dispatch($request);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $this->getResponseProperty($response, 'statusCode'));
        
        $data = $this->getResponseProperty($response, 'data');
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Welcome to the Recipe API', $data['message']);
    }
    
    public function testProtectedRouteChecksAuthentication()
    {
        // Add a protected route
        $this->router->addRoute('GET', '/protected', 'test.controller', 'testAction', true);
        
        // Mock the auth middleware to return a 401 response
        $authResponse = new Response(['error' => 'Unauthorized'], 401);
        $this->authMiddleware->method('process')->willReturn($authResponse);
        
        $request = new Request('/protected', 'GET');
        $response = $this->router->dispatch($request);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(401, $this->getResponseProperty($response, 'statusCode'));
    }
    
    public function testMatchingRoute()
    {
        // Add a normal route
        $this->router->addRoute('GET', '/test', 'test.controller', 'testAction', false);
        
        // Mock the container to return a controller
        $controller = $this->createMock(\stdClass::class);
        $controller->method('testAction')->willReturn(new Response(['result' => 'success'], 200));
        
        $this->container->method('get')->with('test.controller')->willReturn($controller);
        
        $request = new Request('/test', 'GET');
        $response = $this->router->dispatch($request);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $this->getResponseProperty($response, 'statusCode'));
        
        $data = $this->getResponseProperty($response, 'data');
        $this->assertArrayHasKey('result', $data);
        $this->assertEquals('success', $data['result']);
    }
    
    /**
     * Helper method to access protected/private properties of Response objects.
     */
    private function getResponseProperty(Response $response, string $property)
    {
        $reflection = new \ReflectionClass($response);
        $reflectionProperty = $reflection->getProperty($property);
        $reflectionProperty->setAccessible(true);
        
        return $reflectionProperty->getValue($response);
    }
}
