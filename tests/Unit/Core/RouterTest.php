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
        
        // Mock the controller
        $mockController = $this->createMock(\stdClass::class);
        $mockController->method('testAction')->willReturn(new Response(['result' => 'success']));
        
        $this->container->method('get')->with('test.controller')->willReturn($mockController);
        
        $request = new Request('/test', 'GET');
        $response = $this->router->dispatch($request);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $this->getResponseProperty($response, 'statusCode'));
        
        $data = $this->getResponseProperty($response, 'data');
        $this->assertEquals(['result' => 'success'], $data);
    }
    
    // Helper method to access private properties
    private function getResponseProperty(Response $response, string $propertyName)
    {
        $reflection = new \ReflectionClass(Response::class);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($response);
    }
}
