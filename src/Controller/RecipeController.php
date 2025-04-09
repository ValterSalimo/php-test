<?php

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Model\Recipe;
use App\Repository\RecipeRepository;
use App\Service\ValidationService;
use App\Service\LogService;
use Exception;
use App\Exception\DatabaseException;

class RecipeController
{
    private $repository;
    private $validator;
    private $logger;

    public function __construct(RecipeRepository $repository, ValidationService $validator = null, LogService $logger = null)
    {
        $this->repository = $repository;
        $this->validator = $validator;
        $this->logger = $logger;
    }

    public function listAll(Request $request)
    {
        $page = (int) $request->getParam('page', 1);
        $limit = (int) $request->getParam('limit', 10);
        
        $offset = ($page - 1) * $limit;
        $recipes = $this->repository->findAll($limit, $offset);
        
        return new Response([
            'data' => array_map(function(Recipe $recipe) {
                return $recipe->toArray();
            }, $recipes),
            'page' => $page,
            'limit' => $limit
        ]);
    }

    public function getOne(Request $request)
    {
        $id = $request->getParam('id');
        $recipe = $this->repository->findById($id);
        
        if (!$recipe) {
            return new Response(['error' => 'Recipe not found'], 404);
        }
        
        return new Response($recipe->toArray());
    }

    public function create(Request $request)
    {
        $data = $request->getBody();
        
        if (!isset($data['name']) || !isset($data['prepTime']) || !isset($data['difficulty'])) {
            return new Response(['error' => 'Missing required fields'], 400);
        }
        
        try {
            $recipe = new Recipe();
            $recipe->setName($data['name']);
            $recipe->setPrepTime((int)$data['prepTime']);
            $recipe->setDifficulty((int)$data['difficulty']);
            
            
            if (isset($data['vegetarian'])) {
                $vegetarian = filter_var($data['vegetarian'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
             
                $recipe->setVegetarian($vegetarian !== null ? $vegetarian : false);
                
                if ($this->logger) {
                    $this->logger->info("Vegetarian value processed", [
                        'original' => $data['vegetarian'],
                        'processed' => $recipe->isVegetarian() 
                    ]);
                }
            } else {
                $recipe->setVegetarian(false);
            }
            
            $this->repository->save($recipe);
            
            return new Response($recipe->toArray(), 201);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error("Recipe creation failed", ['error' => $e->getMessage()]);
            }
            return new Response(['error' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request)
    {
        $id = $request->getParam('id');
        $data = $request->getBody();
        
        $recipe = $this->repository->findById($id);
        if (!$recipe) {
            return new Response(['error' => 'Recipe not found'], 404);
        }
        
        try {
            if (isset($data['name'])) {
                $recipe->setName($data['name']);
            }
            
            if (isset($data['prepTime'])) {
                $recipe->setPrepTime($data['prepTime']);
            }
            
            if (isset($data['difficulty'])) {
                $recipe->setDifficulty($data['difficulty']);
            }
            
            if (isset($data['vegetarian'])) {
                $recipe->setVegetarian($data['vegetarian']);
            }
            
            $this->repository->save($recipe);
            
            return new Response($recipe->toArray());
        } catch (\Exception $e) {
            return new Response(['error' => $e->getMessage()], 400);
        }
    }

    public function delete(Request $request)
    {
        $id = $request->getParam('id');
        
        $recipe = $this->repository->findById($id);
        if (!$recipe) {
            return new Response(['error' => 'Recipe not found'], 404);
        }
        
        $this->repository->delete($id);
        
        return new Response(null, 204);
    }

    public function rate(Request $request)
    {
        $id = $request->getParam('id');
        $data = $request->getBody();
        
        if (!isset($data['rating']) || !is_numeric($data['rating'])) {
            return new Response(['error' => 'Rating is required and must be a number'], 400);
        }
        
        $rating = (int) $data['rating'];
        
        if ($rating < 1 || $rating > 5) {
            return new Response(['error' => 'Rating must be between 1 and 5'], 400);
        }
        
        $recipe = $this->repository->findById($id);
        if (!$recipe) {
            return new Response(['error' => 'Recipe not found'], 404);
        }
        
        $this->repository->addRating($id, $rating);
        $recipe = $this->repository->findById($id); 
        
        return new Response($recipe->toArray());
    }

    public function search(Request $request)
    {
        try {
            // Log the raw request for debugging
            error_log("Search request received: " . $_SERVER['REQUEST_URI']);
            
            // Get raw params for debugging
            $rawParams = $request->getParams();
            
            // Log raw parameters if logger available
            if ($this->logger) {
                $this->logger->info('Raw search parameters', $rawParams);
            } else {
                error_log("Search params: " . json_encode($rawParams));
            }
            
            // Get and sanitize search parameters
            $query = $request->getParam('q', '');
            if (is_string($query)) {
                $query = trim($query);
            } else {
                $query = '';
            }
            
            // Handle vegetarian param - convert string to boolean properly
            $vegetarianParam = $request->getParam('vegetarian');
            $vegetarianOnly = false;
            if ($vegetarianParam !== null) {
                // Convert various forms of true/false
                if (is_string($vegetarianParam)) {
                    $vegetarianParam = trim($vegetarianParam);
                }
                if ($vegetarianParam === 'true' || $vegetarianParam === '1' || $vegetarianParam === true || $vegetarianParam === 1) {
                    $vegetarianOnly = true;
                }
            }
            
            // Handle difficulty param - ensure it's numeric
            $difficultyParam = $request->getParam('difficulty');
            $difficulty = null;
            if ($difficultyParam !== null) {
                if (is_string($difficultyParam)) {
                    $difficultyParam = trim($difficultyParam);
                }
                if ($difficultyParam !== '' && is_numeric($difficultyParam)) {
                    $difficulty = (int)$difficultyParam;
                    
                    // Validate difficulty range
                    if ($difficulty < 1 || $difficulty > 3) {
                        return new Response(['error' => 'Difficulty must be between 1 and 3'], 400);
                    }
                }
            }
            
            // Log processed search parameters
            if ($this->logger) {
                $this->logger->info('Processed search parameters', [
                    'query' => $query,
                    'vegetarian' => $vegetarianOnly,
                    'difficulty' => $difficulty
                ]);
            } else {
                error_log("Processed search params: query=" . $query . 
                          ", vegetarian=" . ($vegetarianOnly ? 'true' : 'false') . 
                          ", difficulty=" . ($difficulty ?? 'null'));
            }
            
            // Execute search with error handling
            try {
                $recipes = $this->repository->search($query, $vegetarianOnly, $difficulty);
                
                // Log successful results
                if ($this->logger) {
                    $this->logger->info('Search results', ['count' => count($recipes)]);
                } else {
                    error_log("Search returned " . count($recipes) . " results");
                }
                
                return new Response([
                    'data' => array_map(function(Recipe $recipe) {
                        return $recipe->toArray();
                    }, $recipes)
                ]);
            } catch (\Exception $e) {
                if ($this->logger) {
                    $this->logger->error('Search repository error', [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                } else {
                    error_log("Search repository error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                }
                throw $e;
            }
        } catch (\Exception $e) {
            // Log the complete error
            if ($this->logger) {
                $this->logger->error('Recipe search failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
                ]);
            } else {
                error_log("Recipe search failed: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            }
            
            // Return a user-friendly error message
            return new Response(['error' => 'An error occurred while searching recipes: ' . $e->getMessage()], 500);
        }
    }
}
