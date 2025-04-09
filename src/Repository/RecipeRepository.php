<?php

namespace App\Repository;

use App\Database\PostgresDatabase;
use App\Model\Recipe;
use PDO;
use Exception;
use App\Exception\DatabaseException;

class RecipeRepository
{
    private $db;

    public function __construct(PostgresDatabase $db)
    {
        $this->db = $db;
    }

    /**
     * Find all recipes with pagination
     * 
     * @param int $limit Maximum number of records to return
     * @param int $offset Number of records to skip
     * @return Recipe[] Array of Recipe objects
     * @throws DatabaseException
     */
    public function findAll($limit = 10, $offset = 0)
    {
        try {
            error_log("Finding all recipes with limit: $limit, offset: $offset");
            
            $sql = "SELECT r.*, 
                    COALESCE(
                        (SELECT json_agg(rating) FROM recipe_ratings WHERE recipe_id = r.id),
                        '[]'::json
                    ) AS ratings_json
                FROM recipes r
                ORDER BY r.id
                LIMIT :limit OFFSET :offset";
                
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $recipes = [];
            while ($row = $stmt->fetch()) {
                $recipes[] = $this->hydrateRecipe($row);
            }
            
            error_log("Found " . count($recipes) . " recipes");
            return $recipes;
        } catch (Exception $e) {
            error_log("Error fetching recipes: " . $e->getMessage());
            throw new DatabaseException('Failed to fetch recipes: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Find a recipe by ID
     * 
     * @param int $id Recipe ID
     * @return Recipe|null Recipe object or null if not found
     * @throws DatabaseException
     */
    public function findById($id)
    {
        try {
            $sql = "SELECT r.*, 
                    COALESCE(
                        (SELECT json_agg(rating) FROM recipe_ratings WHERE recipe_id = r.id),
                        '[]'::json
                    ) AS ratings_json
                FROM recipes r
                WHERE r.id = :id";
                
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $row = $stmt->fetch();
            if (!$row) {
                return null;
            }
            
            return $this->hydrateRecipe($row);
        } catch (Exception $e) {
            throw new DatabaseException('Failed to fetch recipe: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Search for recipes with filters
     * 
     * @param string|null $query Search term for name
     * @param bool $vegetarianOnly Whether to only return vegetarian recipes
     * @param int|null $difficulty Difficulty level filter
     * @return Recipe[] Array of matching Recipe objects
     * @throws DatabaseException
     */
    public function search($query = null, $vegetarianOnly = false, $difficulty = null)
    {
        $params = [];
        $conditions = [];
        $sql = '';
        
        try {
            // Build the base SQL query
            $baseSql = "SELECT r.*, 
                    COALESCE(
                        (SELECT json_agg(rating) FROM recipe_ratings WHERE recipe_id = r.id),
                        '[]'::json
                    ) AS ratings_json
                FROM recipes r
                WHERE 1=1";
            
            // Use prepared statement with named parameters for safety
            if (!empty($query) && is_string($query) && trim($query) !== '') {
                $conditions[] = "r.name ILIKE :query";
                $params[':query'] = '%' . trim($query) . '%';
            }
            
            if ($vegetarianOnly) {
                $conditions[] = "r.vegetarian = :vegetarian";
                $params[':vegetarian'] = 't'; // PostgreSQL text representation of true
            }
            
            if ($difficulty !== null && is_numeric($difficulty)) {
                $conditions[] = "r.difficulty = :difficulty";
                $params[':difficulty'] = (int)$difficulty;
            }
            
            // Build the complete SQL query
            $sql = $baseSql;
            if (!empty($conditions)) {
                $sql .= " AND " . implode(" AND ", $conditions);
            }
            $sql .= " ORDER BY r.id";
            
            // Log the SQL and parameters for debugging
            error_log("Search SQL: " . $sql);
            error_log("Search params: " . json_encode($params));
            
            // Prepare and execute the statement
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $type = PDO::PARAM_STR;
                if (is_int($value)) {
                    $type = PDO::PARAM_INT;
                }
                $stmt->bindValue($key, $value, $type);
            }
            $stmt->execute();
            
            // Process results
            $recipes = [];
            while ($row = $stmt->fetch()) {
                $recipes[] = $this->hydrateRecipe($row);
            }
            
            return $recipes;
        } catch (\PDOException $e) {
            error_log("PDO Error in search: " . $e->getMessage() . " (Code: " . $e->getCode() . ")");
            error_log("SQL: " . ($sql ?? 'unknown'));
            error_log("Params: " . json_encode($params));
            throw new DatabaseException('Database error during search: ' . $e->getMessage(), $e->getCode(), $e);
        } catch (\Exception $e) {
            error_log("General Error in search: " . $e->getMessage());
            throw new DatabaseException('Failed to search recipes: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Save a recipe (insert or update)
     * 
     * @param Recipe $recipe Recipe to save
     * @return Recipe Saved recipe with ID
     * @throws DatabaseException
     */
    public function save(Recipe $recipe)
    {
        try {
            $this->db->beginTransaction();
            
            if ($recipe->getId()) {
                $result = $this->update($recipe);
            } else {
                $result = $this->insert($recipe);
            }
            
            $this->db->commit();
            return $result;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new DatabaseException('Failed to save recipe: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Insert a new recipe
     * 
     * @param Recipe $recipe Recipe to insert
     * @return Recipe Recipe with new ID
     */
    private function insert(Recipe $recipe)
    {
        $sql = "INSERT INTO recipes (name, prep_time, difficulty, vegetarian) 
                VALUES (:name, :prepTime, :difficulty, :vegetarian) 
                RETURNING id";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':name', $recipe->getName());
        $stmt->bindValue(':prepTime', $recipe->getPrepTime(), PDO::PARAM_INT);
        $stmt->bindValue(':difficulty', $recipe->getDifficulty(), PDO::PARAM_INT);
        $stmt->bindValue(':vegetarian', $recipe->isVegetarian(), PDO::PARAM_BOOL);
        $stmt->execute();
        
        $id = $stmt->fetchColumn();
        $recipe->setId($id);
        
        return $recipe;
    }

    /**
     * Update an existing recipe
     * 
     * @param Recipe $recipe Recipe to update
     * @return Recipe Updated recipe
     */
    private function update(Recipe $recipe)
    {
        $sql = "UPDATE recipes 
                SET name = :name, prep_time = :prepTime, 
                    difficulty = :difficulty, vegetarian = :vegetarian
                WHERE id = :id";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':name', $recipe->getName());
        $stmt->bindValue(':prepTime', $recipe->getPrepTime(), PDO::PARAM_INT);
        $stmt->bindValue(':difficulty', $recipe->getDifficulty(), PDO::PARAM_INT);
        $stmt->bindValue(':vegetarian', $recipe->isVegetarian(), PDO::PARAM_BOOL);
        $stmt->bindValue(':id', $recipe->getId(), PDO::PARAM_INT);
        $stmt->execute();
        
        return $recipe;
    }

    /**
     * Delete a recipe by ID
     * 
     * @param int $id Recipe ID to delete
     * @return bool True if deleted successfully
     * @throws DatabaseException
     */
    public function delete($id)
    {
        try {
            $this->db->beginTransaction();
            
            // First delete ratings
            $sql = "DELETE FROM recipe_ratings WHERE recipe_id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Then delete the recipe
            $sql = "DELETE FROM recipes WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->rowCount() > 0;
            
            $this->db->commit();
            return $result;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new DatabaseException('Failed to delete recipe: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Add a rating to a recipe
     * 
     * @param int $recipeId Recipe ID
     * @param int $rating Rating value
     * @return bool True if added successfully
     * @throws DatabaseException
     */
    public function addRating($recipeId, $rating)
    {
        try {
            $sql = "INSERT INTO recipe_ratings (recipe_id, rating) VALUES (:recipeId, :rating)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':recipeId', $recipeId, PDO::PARAM_INT);
            $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
            $stmt->execute();
            
            return true;
        } catch (Exception $e) {
            throw new DatabaseException('Failed to add rating: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Count total number of recipes
     * 
     * @return int Total number of recipes
     * @throws DatabaseException
     */
    public function count(): int
    {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM recipes");
            return (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            throw new DatabaseException('Failed to count recipes: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Convert database row to Recipe object
     * 
     * @param array $row Database row
     * @return Recipe Hydrated Recipe object
     */
    private function hydrateRecipe($row)
    {
        $recipe = new Recipe(
            $row['id'],
            $row['name'],
            $row['prep_time'],
            $row['difficulty'],
            (bool)$row['vegetarian']
        );
        
        // Use JSON format for ratings instead of array parsing
        if (!empty($row['ratings_json']) && $row['ratings_json'] !== '[]') {
            $ratings = json_decode($row['ratings_json'], true);
            if (is_array($ratings)) {
                foreach ($ratings as $rating) {
                    $recipe->addRating((int)$rating);
                }
            }
        }
        
        return $recipe;
    }
}
