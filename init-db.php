<?php
/**
 * Database initialization script
 * Run with: docker-compose exec php php /server/http/init-db.php
 */

// Check if PDO is loaded
if (!extension_loaded('pdo_pgsql')) {
    echo "Error: PDO PostgreSQL extension not loaded\n";
    exit(1);
}

// Database connection parameters
$host = getenv('DB_HOST') ?: 'postgres';
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME') ?: 'hellofresh';
$username = getenv('DB_USER') ?: 'postgres'; 
$password = getenv('DB_PASSWORD') ?: 'valter123';

echo "Connecting to PostgreSQL at $host:$port/$dbname as $username\n";

try {
    // Connect to PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "Connected successfully!\n";
    
    // Create tables
    $queries = [
        "DROP TABLE IF EXISTS recipe_ratings CASCADE;",
        "DROP TABLE IF EXISTS recipes CASCADE;",
        "DROP TABLE IF EXISTS users CASCADE;",
        
        "CREATE TABLE recipes (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            prep_time INTEGER NOT NULL,
            difficulty INTEGER NOT NULL CHECK (difficulty BETWEEN 1 AND 3),
            vegetarian BOOLEAN NOT NULL DEFAULT FALSE
        );",
        
        "CREATE TABLE recipe_ratings (
            id SERIAL PRIMARY KEY,
            recipe_id INTEGER NOT NULL REFERENCES recipes(id) ON DELETE CASCADE,
            rating INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        );",
        
        "CREATE TABLE users (
            id SERIAL PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        );"
    ];
    
    // Execute each query
    $pdo->beginTransaction();
    try {
        foreach ($queries as $query) {
            echo "Executing: " . substr($query, 0, 50) . "...\n";
            $pdo->exec($query);
        }
        $pdo->commit();
        echo "Tables created successfully!\n";
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
    // Insert test user
    $username = 'testuser';
    $password = password_hash('testpassword', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?) ON CONFLICT (username) DO NOTHING");
    $stmt->execute([$username, $password]);
    echo "Test user 'testuser' with password 'testpassword' created.\n";
    
    // Insert test recipes - Fix the boolean values
    $recipes = [
        ['Spaghetti Carbonara', 30, 2, 'false'], 
        ['Vegetable Stir Fry', 20, 1, 'true'],    
        ['Beef Wellington', 90, 3, 'false']       
    ];
    
    $stmt = $pdo->prepare("INSERT INTO recipes (name, prep_time, difficulty, vegetarian) VALUES (?, ?, ?, ?::boolean)");
    foreach ($recipes as $recipe) {
        $stmt->execute($recipe);
        $recipeId = $pdo->lastInsertId();
        echo "Created recipe: {$recipe[0]} with ID $recipeId\n";
        
        // Add some ratings
        $ratingStmt = $pdo->prepare("INSERT INTO recipe_ratings (recipe_id, rating) VALUES (?, ?)");
        for ($i = 0; $i < 3; $i++) {
            $rating = rand(1, 5);
            $ratingStmt->execute([$recipeId, $rating]);
            echo "  Added rating: $rating\n";
        }
    }
    
    echo "\nDatabase initialization complete!\n";
    echo "You can now access the API at http://localhost:8080/ui\n";
    echo "Use Swagger UI at http://localhost:8080/swagger\n";
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
