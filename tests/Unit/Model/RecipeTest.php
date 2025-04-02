<?php

namespace Tests\Unit\Model;

use App\Model\Recipe;
use PHPUnit\Framework\TestCase;

class RecipeTest extends TestCase
{
    public function testRecipeCreation()
    {
        $recipe = new Recipe(1, 'Test Recipe', 30, 2, true);
        
        $this->assertEquals(1, $recipe->getId());
        $this->assertEquals('Test Recipe', $recipe->getName());
        $this->assertEquals(30, $recipe->getPrepTime());
        $this->assertEquals(2, $recipe->getDifficulty());
        $this->assertTrue($recipe->isVegetarian());
    }
    
    public function testAddRating()
    {
        $recipe = new Recipe(1, 'Test Recipe', 30, 2, true);
        
        $recipe->addRating(4);
        $recipe->addRating(5);
        
        $this->assertEquals(4.5, $recipe->getAvgRating());
        $this->assertEquals([4, 5], $recipe->getRatings());
    }
    
    public function testInvalidDifficulty()
    {
        $recipe = new Recipe();
        
        $this->expectException(\InvalidArgumentException::class);
        $recipe->setDifficulty(5); // Should throw exception
    }
    
    public function testInvalidRating()
    {
        $recipe = new Recipe();
        
        $this->expectException(\InvalidArgumentException::class);
        $recipe->addRating(6); // Should throw exception
    }
    
    public function testToArray()
    {
        $recipe = new Recipe(1, 'Test Recipe', 30, 2, true);
        $recipe->addRating(4);
        
        $expected = [
            'id' => 1,
            'name' => 'Test Recipe',
            'prepTime' => 30,
            'difficulty' => 2,
            'vegetarian' => true,
            'avgRating' => 4.0,
            'ratings' => 1
        ];
        
        $this->assertEquals($expected, $recipe->toArray());
    }
    
    public function testFromArray()
    {
        $data = [
            'id' => 1,
            'name' => 'Test Recipe',
            'prepTime' => 30,
            'difficulty' => 2,
            'vegetarian' => true,
            'ratings' => [4, 5]
        ];
        
        $recipe = Recipe::fromArray($data);
        
        $this->assertEquals(1, $recipe->getId());
        $this->assertEquals('Test Recipe', $recipe->getName());
        $this->assertEquals(30, $recipe->getPrepTime());
        $this->assertEquals(2, $recipe->getDifficulty());
        $this->assertTrue($recipe->isVegetarian());
        $this->assertEquals(4.5, $recipe->getAvgRating());
    }
}
