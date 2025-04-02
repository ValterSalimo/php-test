<?php

namespace App\Model;

class Recipe
{
    private $id;
    private $name;
    private $prepTime;
    private $difficulty;
    private $vegetarian;
    private $ratings = [];
    private $avgRating = 0;

    public function __construct($id = null, $name = null, $prepTime = null, $difficulty = null, $vegetarian = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->prepTime = $prepTime;
        $this->difficulty = $difficulty;
        $this->vegetarian = $vegetarian;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getPrepTime()
    {
        return $this->prepTime;
    }

    public function setPrepTime($prepTime)
    {
        $this->prepTime = $prepTime;
    }

    public function getDifficulty()
    {
        return $this->difficulty;
    }

    public function setDifficulty($difficulty)
    {
        if ($difficulty < 1 || $difficulty > 3) {
            throw new \InvalidArgumentException('Difficulty must be between 1 and 3');
        }
        $this->difficulty = $difficulty;
    }

    public function isVegetarian()
    {
        return $this->vegetarian;
    }

    public function setVegetarian($vegetarian)
    {
        // Make sure we handle various input values correctly
        if (is_string($vegetarian)) {
            // Handle string values like 'true', 'false', '1', '0'
            $this->vegetarian = filter_var($vegetarian, FILTER_VALIDATE_BOOLEAN);
        } else {
            // Handle boolean or int values
            $this->vegetarian = (bool)$vegetarian;
        }
    }

    public function addRating($rating)
    {
        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5');
        }
        $this->ratings[] = $rating;
        $this->recalculateAvgRating();
    }

    public function getRatings()
    {
        return $this->ratings;
    }

    public function getAvgRating()
    {
        return $this->avgRating;
    }

    private function recalculateAvgRating()
    {
        if (empty($this->ratings)) {
            $this->avgRating = 0;
        } else {
            $this->avgRating = array_sum($this->ratings) / count($this->ratings);
        }
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'prepTime' => $this->prepTime,
            'difficulty' => $this->difficulty,
            'vegetarian' => $this->vegetarian,
            'avgRating' => $this->avgRating,
            'ratings' => count($this->ratings)
        ];
    }

    public static function fromArray(array $data)
    {
        $recipe = new self(
            $data['id'] ?? null,
            $data['name'] ?? null,
            $data['prepTime'] ?? null,
            $data['difficulty'] ?? null,
            $data['vegetarian'] ?? null
        );
        
        if (isset($data['ratings'])) {
            foreach ($data['ratings'] as $rating) {
                $recipe->addRating($rating);
            }
        }
        
        return $recipe;
    }
}
