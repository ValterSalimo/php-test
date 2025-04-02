<?php

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Model\Recipe;
use App\Repository\RecipeRepository;
use App\Service\ValidationService;
use App\Service\LogService;

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
            
            // Fix vegetarian boolean handling
            if (isset($data['vegetarian'])) {
                // Convert various vegetarian input types to proper boolean
                $vegetarian = filter_var($data['vegetarian'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                // If null (invalid), default to false
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
        $recipe = $this->repository->findById($id); // Get updated recipe with the new rating
        
        return new Response($recipe->toArray());
    }

    public function search(Request $request)
    {
        $query = $request->getParam('q', '');
        $vegetarian = filter_var($request->getParam('vegetarian', false), FILTER_VALIDATE_BOOLEAN);
        $difficulty = $request->getParam('difficulty');
        
        if ($difficulty !== null) {
            $difficulty = (int) $difficulty;
            if ($difficulty < 1 || $difficulty > 3) {
                return new Response(['error' => 'Difficulty must be between 1 and 3'], 400);
            }
        }
        
        $recipes = $this->repository->search($query, $vegetarian, $difficulty);
        
        return new Response([
            'data' => array_map(function(Recipe $recipe) {
                return $recipe->toArray();
            }, $recipes)
        ]);
    }
}
