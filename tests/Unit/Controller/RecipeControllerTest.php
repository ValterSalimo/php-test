<?php

namespace Tests\Unit\Controller;

use App\Controller\RecipeController;
use App\Core\Request;
use App\Core\Response;
use App\Model\Recipe;
use App\Repository\RecipeRepository;
use PHPUnit\Framework\TestCase;

class RecipeControllerTest extends TestCase
{
    private $repository;
    private $controller;
    
    protected function setUp(): void
    {
        $this->repository = $this->createMock(RecipeRepository::class);
        $this->controller = new RecipeController($this->repository);
    }
    
    public function testListAll()
    {
        $recipe1 = new Recipe(1, 'Recipe 1', 30, 2, false);
        $recipe2 = new Recipe(2, 'Recipe 2', 45, 3, true);
        
        $this->repository->expects($this->once())
            ->method('findAll')
            ->with(10, 0)
            ->willReturn([$recipe1, $recipe2]);
        
        $request = new Request('/recipes', 'GET', ['page' => 1, 'limit' => 10]);
        $response = $this->controller->listAll($request);
        
        $this->assertInstanceOf(Response::class, $response);
        
        $data = $this->getResponseProperty($response, 'data');
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(2, $data['data']);
        $this->assertEquals(1, $data['page']);
        $this->assertEquals(10, $data['limit']);
    }
    
    public function testGetOne()
    {
        $recipe = new Recipe(1, 'Test Recipe', 30, 2, true);
        
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($recipe);
        
        $request = new Request('/recipes/1', 'GET', ['id' => 1]);
        $response = $this->controller->getOne($request);
        
        $this->assertInstanceOf(Response::class, $response);
        
        $data = $this->getResponseProperty($response, 'data');
        $this->assertEquals(1, $data['id']);
        $this->assertEquals('Test Recipe', $data['name']);
    }
    
    public function testGetOneNotFound()
    {
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);
        
        $request = new Request('/recipes/999', 'GET', ['id' => 999]);
        $response = $this->controller->getOne($request);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(404, $this->getResponseProperty($response, 'statusCode'));
    }
    
    public function testCreate()
    {
        $recipeData = [
            'name' => 'New Recipe',
            'prepTime' => 30,
            'difficulty' => 2,
            'vegetarian' => true
        ];
        
        $this->repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function($recipe) {
                $recipe->setId(1);
                return $recipe;
            });
        
        $request = new Request('/recipes', 'POST', [], $recipeData);
        $response = $this->controller->create($request);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(201, $this->getResponseProperty($response, 'statusCode'));
        
        $data = $this->getResponseProperty($response, 'data');
        $this->assertEquals(1, $data['id']);
        $this->assertEquals('New Recipe', $data['name']);
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
