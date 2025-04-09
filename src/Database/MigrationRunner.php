<?php

namespace App\Database;

use App\Database\PostgresDatabase;
use App\Service\LogService;

class MigrationRunner
{
    private $db;
    private $logger;
    
    public function __construct(PostgresDatabase $db, LogService $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->ensureMigrationTableExists();
    }
    
    private function ensureMigrationTableExists()
    {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->db->query($sql);
    }
    
    public function getMigrationsPath()
    {
        return __DIR__ . '/Migrations';
    }
    
    public function getExecutedMigrations()
    {
        $sql = "SELECT name FROM migrations ORDER BY id";
        return $this->db->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
    }
    
    public function migrate()
    {
        $executedMigrations = $this->getExecutedMigrations();
        $migrationsPath = $this->getMigrationsPath();
        
        if (!is_dir($migrationsPath)) {
            $this->logger->error("Migrations directory does not exist: $migrationsPath");
            return false;
        }
        
        $migrations = [];
        
        // Find all migration files
        foreach (new \DirectoryIterator($migrationsPath) as $file) {
            if ($file->isDot() || $file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }
            
            $className = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $fullClassName = 'App\\Database\\Migrations\\' . $className;
            
            if (!class_exists($fullClassName)) {
                require_once $file->getPathname();
            }
            
            if (!class_exists($fullClassName)) {
                $this->logger->error("Migration class not found: $fullClassName");
                continue;
            }
            
            $migrations[] = $fullClassName;
        }
        
        sort($migrations);
        
        $this->db->beginTransaction();
        
        try {
            foreach ($migrations as $migrationClass) {
                $migration = new $migrationClass($this->db);
                $migrationName = $migration->getName();
                
                if (in_array($migrationName, $executedMigrations)) {
                    $this->logger->info("Migration already executed: $migrationName");
                    continue;
                }
                
                $this->logger->info("Running migration: $migrationName");
                $migration->up();
                
                $this->db->query("INSERT INTO migrations (name) VALUES (?)", [$migrationName]);
                $this->logger->info("Migration completed: $migrationName");
            }
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            $this->logger->error("Migration failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function rollback()
    {
        $executedMigrations = array_reverse($this->getExecutedMigrations());
        
        if (empty($executedMigrations)) {
            $this->logger->info("No migrations to roll back");
            return true;
        }
        
        $this->db->beginTransaction();
        
        try {
            $lastMigration = $executedMigrations[0];
            $migrationClass = 'App\\Database\\Migrations\\' . $lastMigration;
            
            if (!class_exists($migrationClass)) {
                $file = $this->getMigrationsPath() . '/' . $lastMigration . '.php';
                if (file_exists($file)) {
                    require_once $file;
                }
            }
            
            if (!class_exists($migrationClass)) {
                throw new \Exception("Migration class not found: $migrationClass");
            }
            
            $migration = new $migrationClass($this->db);
            $this->logger->info("Rolling back migration: $lastMigration");
            $migration->down();
            
            $this->db->query("DELETE FROM migrations WHERE name = ?", [$lastMigration]);
            $this->logger->info("Rolled back migration: $lastMigration");
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            $this->logger->error("Rollback failed: " . $e->getMessage());
            return false;
        }
    }
}
