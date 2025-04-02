<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the Recipe API endpoints
 */
class RecipeApiTest extends TestCase
{
    private string $baseUrl = 'http://localhost:8080';
    private ?string $authToken = null;

    protected function setUp(): void
    {
        // Login to get an auth token
        $this->authToken = $this->getAuthToken();
    }

    private function getAuthToken(): string
    {
        $response = $this->makeRequest('/auth/login', 'POST', [
            'username' => 'testuser',
            'password' => 'testpassword'
        ]);
        
        $this->assertArrayHasKey('token', $response);
        return $response['token'];
    }

    public function testListRecipes(): void
    {
        $response = $this->makeRequest('/recipes', 'GET');
        
        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
    }
    
    public function testCreateRecipe(): void
    {
        $recipe = [
            'name' => 'Test Recipe ' . uniqid(),
            'prepTime' => 30,
            'difficulty' => 2,
            'vegetarian' => false
        ];
        
        $response = $this->makeRequest('/recipes', 'POST', $recipe, true);
        
        $this->assertArrayHasKey('id', $response);
        $this->assertEquals($recipe['name'], $response['name']);
        
        return $response['id'];
    }
    
    /**
     * @depends testCreateRecipe
     */
    public function testGetRecipe(int $recipeId): void
    {
        $response = $this->makeRequest('/recipes/' . $recipeId, 'GET');
        
        $this->assertEquals($recipeId, $response['id']);
    }
    
    /**
     * @depends testCreateRecipe
     */
    public function testUpdateRecipe(int $recipeId): void
    {
        $update = [
            'name' => 'Updated Recipe ' . uniqid(),
            'prepTime' => 45
        ];
        
        $response = $this->makeRequest('/recipes/' . $recipeId, 'PUT', $update, true);
        
        $this->assertEquals($recipeId, $response['id']);
        $this->assertEquals($update['name'], $response['name']);
        $this->assertEquals($update['prepTime'], $response['prepTime']);
    }
    
    /**
     * @depends testCreateRecipe
     */
    public function testRateRecipe(int $recipeId): void
    {
        $rating = ['rating' => 4];
        
        $response = $this->makeRequest('/recipes/' . $recipeId . '/rating', 'POST', $rating);
        
        $this->assertEquals($recipeId, $response['id']);
        $this->assertEquals(4, $response['avgRating']);
    }
    
    /**
     * @depends testCreateRecipe
     */
    public function testSearchRecipes(int $recipeId): void
    {
        // Get the recipe first to know its name
        $recipe = $this->makeRequest('/recipes/' . $recipeId, 'GET');
        $searchTerm = substr($recipe['name'], 0, 5);
        
        $response = $this->makeRequest('/recipes/search?q=' . urlencode($searchTerm), 'GET');
        
        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
        $this->assertGreaterThan(0, count($response['data']));
    }
    
    /**
     * @depends testCreateRecipe
     */
    public function testDeleteRecipe(int $recipeId): void
    {
        $response = $this->makeRequest('/recipes/' . $recipeId, 'DELETE', [], true);
        
        // Delete should return 204 No Content
        $this->assertNull($response);
        
        // Verify recipe is deleted
        $ch = curl_init($this->baseUrl . '/recipes/' . $recipeId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $this->assertEquals(404, $statusCode);
    }

    /**
     * Helper method to make HTTP requests
     */
    private function makeRequest(string $endpoint, string $method = 'GET', array $data = [], bool $auth = false)
    {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        $headers = ['Content-Type: application/json'];
        
        // Add authentication if required
        if ($auth && $this->authToken) {
            $headers[] = 'Authorization: Bearer ' . $this->authToken;
        }
        
        // Add data for POST/PUT requests
        if (in_array($method, ['POST', 'PUT']) && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // For successful requests
        if ($statusCode >= 200 && $statusCode < 300) {
            if ($statusCode === 204) {
                return null; // No content
            }
            return json_decode($response, true);
        }
        
        // For error responses
        $this->fail("API request failed with status code $statusCode: $response");
    }
}
