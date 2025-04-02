<?php

namespace App\Database;

use App\Database\DatabaseInterface;
use PDO;

class PostgresDatabase implements DatabaseInterface
{
    protected $pdo;

    public function __construct($host, $port, $dbname, $username, $password)
    {
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
        try {
            $this->pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // Don't automatically initialize tables anymore
            // This will be handled by the init-db.php script
        } catch (\PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw $e;
        }
    }

    public function prepare($sql)
    {
        return $this->pdo->prepare($sql);
    }

    public function execute($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function query($sql)
    {
        return $this->pdo->query($sql);
    }

    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    public function commit()
    {
        return $this->pdo->commit();
    }

    public function rollback()
    {
        return $this->pdo->rollBack();
    }

    public function executeSetupQueries()
    {
        error_log("Checking if tables exist...");
        
        // Check if tables exist first
        $tables = ["recipes", "recipe_ratings", "users"];
        $existingTables = [];
        
        foreach ($tables as $table) {
            $stmt = $this->pdo->prepare("SELECT to_regclass('public.$table') AS exists");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['exists'] !== null) {
                $existingTables[] = $table;
            }
        }
        
        if (count($existingTables) === count($tables)) {
            error_log("All tables already exist. Skipping table creation.");
            return;
        }
        
        error_log("Creating tables...");
        
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
            error_log("Database tables created successfully.");
        } catch (\Exception $e) {
            $this->pdo->exec("ROLLBACK");
            error_log("Error creating database tables: " . $e->getMessage());
            throw $e;
        }
    }
}
