<?php

namespace App\Repository;

use App\Model\Recipe;
use App\Database\DatabaseInterface;

class RecipeRepository
{
    private $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function findAll($limit = 10, $offset = 0)
    {
        $sql = "SELECT r.*, 
                  (SELECT ARRAY_AGG(rating) FROM recipe_ratings WHERE recipe_id = r.id) AS ratings
               FROM recipes r
               ORDER BY r.id
               LIMIT ? OFFSET ?";
               
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit, $offset]);
        
        $recipes = [];
        while ($row = $stmt->fetch()) {
            $recipe = $this->hydrateRecipe($row);
            $recipes[] = $recipe;
        }
        
        return $recipes;
    }

    public function findById($id)
    {
        $sql = "SELECT r.*, 
                  (SELECT ARRAY_AGG(rating) FROM recipe_ratings WHERE recipe_id = r.id) AS ratings
               FROM recipes r
               WHERE r.id = ?";
               
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        
        return $this->hydrateRecipe($row);
    }

    public function search($query, $vegetarianOnly = false, $difficulty = null)
    {
        $params = [];
        $conditions = [];
        
        $sql = "SELECT r.*, 
                  (SELECT ARRAY_AGG(rating) FROM recipe_ratings WHERE recipe_id = r.id) AS ratings
               FROM recipes r
               WHERE 1=1";
        
        if (!empty($query)) {
            $conditions[] = "r.name ILIKE ?";
            $params[] = "%$query%";
        }
        
        if ($vegetarianOnly) {
            $conditions[] = "r.vegetarian = true";
        }
        
        if ($difficulty) {
            $conditions[] = "r.difficulty = ?";
            $params[] = $difficulty;
        }
        
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }
        
        $sql .= " ORDER BY r.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $recipes = [];
        while ($row = $stmt->fetch()) {
            $recipe = $this->hydrateRecipe($row);
            $recipes[] = $recipe;
        }
        
        return $recipes;
    }

    public function save(Recipe $recipe)
    {
        if ($recipe->getId()) {
            return $this->update($recipe);
        } else {
            return $this->insert($recipe);
        }
    }

    private function insert(Recipe $recipe)
    {
        try {
            // Ensure we convert the boolean to a PostgreSQL-compatible format
            $vegetarian = $recipe->isVegetarian() ? 'true' : 'false';
            
            $sql = "INSERT INTO recipes (name, prep_time, difficulty, vegetarian) 
                    VALUES (?, ?, ?, ?::boolean) 
                    RETURNING id";
                    
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $recipe->getName(),
                $recipe->getPrepTime(),
                $recipe->getDifficulty(),
                $vegetarian  // Properly formatted for PostgreSQL
            ]);
            
            $id = $stmt->fetchColumn();
            $recipe->setId($id);
            
            error_log("Recipe inserted with ID: {$id}");
            
            return $recipe;
        } catch (\Exception $e) {
            error_log("Error inserting recipe: " . $e->getMessage());
            throw $e;
        }
    }

    private function update(Recipe $recipe)
    {
        // Ensure we convert the boolean to a PostgreSQL-compatible format
        $vegetarian = $recipe->isVegetarian() ? 'true' : 'false';
        
        $sql = "UPDATE recipes 
                SET name = ?, prep_time = ?, difficulty = ?, vegetarian = ?::boolean
                WHERE id = ?";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $recipe->getName(),
            $recipe->getPrepTime(),
            $recipe->getDifficulty(),
            $vegetarian,  // Properly formatted for PostgreSQL
            $recipe->getId()
        ]);
        
        return $recipe;
    }

    public function delete($id)
    {
        // First delete ratings
        $sql = "DELETE FROM recipe_ratings WHERE recipe_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        // Then delete the recipe
        $sql = "DELETE FROM recipes WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->rowCount() > 0;
    }

    public function addRating($recipeId, $rating)
    {
        try {
            $sql = "INSERT INTO recipe_ratings (recipe_id, rating) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$recipeId, $rating]);
            
            error_log("Rating {$rating} added to recipe {$recipeId}");
            
            return true;
        } catch (\Exception $e) {
            error_log("Error adding rating: " . $e->getMessage());
            throw $e;
        }
    }

    private function hydrateRecipe($row)
    {
        $recipe = new Recipe(
            $row['id'],
            $row['name'],
            $row['prep_time'],
            $row['difficulty'],
            (bool)$row['vegetarian']
        );
        
        if (!empty($row['ratings'])) {
            $ratings = $this->parsePostgresArray($row['ratings']);
            foreach ($ratings as $rating) {
                $recipe->addRating($rating);
            }
        }
        
        return $recipe;
    }

    private function parsePostgresArray($arrayString)
    {
        if ($arrayString === null) {
            return [];
        }
        
        // Remove curly braces
        $arrayString = trim($arrayString, '{}');
        
        if (empty($arrayString)) {
            return [];
        }
        
        // Split by comma
        return explode(',', $arrayString);
    }
}
