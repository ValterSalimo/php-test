<?php

namespace Tests\Model;

use PHPUnit\Framework\TestCase;
use App\Model\Recipe;

class RecipeTest extends TestCase
{
    public function testCreateRecipe()
    {
        $recipe = new Recipe(1, 'Pasta Carbonara', 30, 2, false);
        
        $this->assertEquals(1, $recipe->getId());
        $this->assertEquals('Pasta Carbonara', $recipe->getName());
        $this->assertEquals(30, $recipe->getPrepTime());
        $this->assertEquals(2, $recipe->getDifficulty());
        $this->assertFalse($recipe->isVegetarian());
    }
    
    public function testAddRating()
    {
        $recipe = new Recipe(1, 'Pasta Carbonara', 30, 2, false);
        
        $recipe->addRating(4);
        $recipe->addRating(5);
        
        $this->assertEquals([4, 5], $recipe->getRatings());
        $this->assertEquals(4.5, $recipe->getAvgRating());
    }
    
    public function testInvalidDifficulty()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $recipe = new Recipe();
        $recipe->setDifficulty(5); // Should throw exception
    }
    
    public function testInvalidRating()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $recipe = new Recipe();
        $recipe->addRating(6); // Should throw exception
    }
    
    public function testToArray()
    {
        $recipe = new Recipe(1, 'Pasta Carbonara', 30, 2, false);
        $recipe->addRating(4);
        
        $array = $recipe->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals(1, $array['id']);
        $this->assertEquals('Pasta Carbonara', $array['name']);
        $this->assertEquals(30, $array['prepTime']);
        $this->assertEquals(2, $array['difficulty']);
        $this->assertFalse($array['vegetarian']);
        $this->assertEquals(4, $array['avgRating']);
        $this->assertEquals(1, $array['ratings']);
    }
    
    public function testFromArray()
    {
        $array = [
            'id' => 1,
            'name' => 'Pasta Carbonara',
            'prepTime' => 30,
            'difficulty' => 2,
            'vegetarian' => false,
            'ratings' => [4, 5]
        ];
        
        $recipe = Recipe::fromArray($array);
        
        $this->assertEquals(1, $recipe->getId());
        $this->assertEquals('Pasta Carbonara', $recipe->getName());
        $this->assertEquals(30, $recipe->getPrepTime());
        $this->assertEquals(2, $recipe->getDifficulty());
        $this->assertFalse($recipe->isVegetarian());
        $this->assertEquals([4, 5], $recipe->getRatings());
        $this->assertEquals(4.5, $recipe->getAvgRating());
    }
}
