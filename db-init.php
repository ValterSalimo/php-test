<?php
/**
 * This script initializes the database tables
 * Run with: docker-compose exec php php /server/http/db-init.php
 */

// We're using CLI here, so echo is fine
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
    // Modify the constructor to not echo anything
    $db = new DatabaseInitializer($host, $port, $dbname, $username, $password);
    
    // Tables are created in the constructor via executeSetupQueries()
    echo "Database initialization complete!\n";
    
    // Add a test user for convenience
    $sql = "INSERT INTO users (username, password) VALUES (?, ?) 
            ON CONFLICT (username) DO NOTHING";
    $stmt = $db->prepare($sql);
    $stmt->execute(['testuser', password_hash('testpassword', PASSWORD_DEFAULT)]);
    
    echo "Test user 'testuser' with password 'testpassword' is available for testing.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Special class for DB initialization that only echoes in CLI mode
class DatabaseInitializer extends PostgresDatabase
{
    public function executeSetupQueries()
    {
        $queries = [
            "CREATE TABLE IF NOT EXISTS recipes (
                id SERIAL PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                prep_time INTEGER NOT NULL,
                difficulty INTEGER NOT NULL CHECK (difficulty BETWEEN 1 AND 3),
                vegetarian BOOLEAN NOT NULL DEFAULT FALSE
            )",
            "CREATE TABLE IF NOT EXISTS recipe_ratings (
                id SERIAL PRIMARY KEY,
                recipe_id INTEGER NOT NULL REFERENCES recipes(id) ON DELETE CASCADE,
                rating INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS users (
                id SERIAL PRIMARY KEY,
                username VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )"
        ];

        try {
            $this->pdo->exec("BEGIN");
            foreach ($queries as $query) {
                $this->pdo->exec($query);
            }
            $this->pdo->exec("COMMIT");
            echo "Database tables created successfully.\n";
        } catch (\Exception $e) {
            $this->pdo->exec("ROLLBACK");
            echo "Error creating database tables: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}
