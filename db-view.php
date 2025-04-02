<?php
/**
 * Simple database viewer script
 * Run with: docker-compose exec php php /server/http/db-view.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Database\PostgresDatabase;

// Database connection parameters
$host = getenv('DB_HOST') ?: 'postgres';
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME') ?: 'hellofresh';
$username = getenv('DB_USER') ?: 'postgres';
$password = getenv('DB_PASSWORD') ?: 'valter123';

echo "Connecting to database at {$host}:{$port}/{$dbname} as {$username}\n";

try {
    // Connect to database
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "Connected successfully!\n\n";
    
    // View tables
    echo "=== RECIPES TABLE ===\n";
    $stmt = $pdo->query("SELECT * FROM recipes ORDER BY id");
    $recipes = $stmt->fetchAll();
    
    echo "Found " . count($recipes) . " recipes\n";
    foreach ($recipes as $recipe) {
        echo "ID: {$recipe['id']}, Name: {$recipe['name']}, ";
        echo "Prep Time: {$recipe['prep_time']}, ";
        echo "Difficulty: {$recipe['difficulty']}, ";
        echo "Vegetarian: " . ($recipe['vegetarian'] ? 'Yes' : 'No') . "\n";
        
        // Get ratings for this recipe
        $ratingStmt = $pdo->prepare("SELECT rating FROM recipe_ratings WHERE recipe_id = ?");
        $ratingStmt->execute([$recipe['id']]);
        $ratings = $ratingStmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($ratings)) {
            $avgRating = array_sum($ratings) / count($ratings);
            echo "  Ratings: " . implode(', ', $ratings) . " (Avg: " . number_format($avgRating, 1) . ")\n";
        } else {
            echo "  No ratings yet\n";
        }
        echo "\n";
    }
    
    echo "\n=== USERS TABLE ===\n";
    $stmt = $pdo->query("SELECT id, username, created_at FROM users");
    $users = $stmt->fetchAll();
    
    echo "Found " . count($users) . " users\n";
    foreach ($users as $user) {
        echo "ID: {$user['id']}, Username: {$user['username']}, ";
        echo "Created: {$user['created_at']}\n";
    }
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
