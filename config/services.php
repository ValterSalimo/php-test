<?php

declare(strict_types=1);

use App\Core\Router;
use App\Database\PostgresDatabase;
use App\Repository\RecipeRepository;
use App\Controller\RecipeController;
use App\Controller\AuthController;
use App\Controller\SwaggerController;
use App\Controller\UIController;
use App\Service\AuthService;
use App\Service\LogService;
use App\Service\ValidationService;
use App\Service\RateLimitService;
use App\Middleware\AuthMiddleware;

// Service for logging
$container->set('log.service', function ($container) {
    return new LogService($_ENV['LOG_FILE'] ?? '/server/http/logs/app.log');
});

// Database connection
$container->set('database', function ($container) {
    $logger = $container->get('log.service');
    
    $host = $_ENV['DB_HOST'] ?? 'postgres';
    $port = $_ENV['DB_PORT'] ?? '5432';
    $dbname = $_ENV['DB_NAME'] ?? 'hellofresh';
    $user = $_ENV['DB_USER'] ?? 'postgres';
    $password = $_ENV['DB_PASSWORD'] ?? 'valter123';
    
    $logger->info("Connecting to database", [
        'host' => $host,
        'port' => $port,
        'dbname' => $dbname,
        'user' => $user
    ]);
    
    try {
        $db = new PostgresDatabase($host, $port, $dbname, $user, $password);
        $logger->info("Database connection successful");
        
        // Setup tables if needed
        $db->executeSetupQueries();
        
        return $db;
    } catch (\Exception $e) {
        $logger->error("Database connection failed", ['message' => $e->getMessage()]);
        throw $e;
    }
});

// Mock Redis object for when Redis extension is not available
class MockRedis {
    private $data = [];
    
    public function connect($host, $port) {
        return true;
    }
    
    public function get($key) {
        return $this->data[$key] ?? null;
    }
    
    public function incr($key) {
        if (!isset($this->data[$key])) {
            $this->data[$key] = 0;
        }
        return ++$this->data[$key];
    }
    
    public function expire($key, $seconds) {
        // In a real implementation, we would set up a timer
        return true;
    }
    
    public function ttl($key) {
        // Always return 60 seconds for mocking purposes
        return 60;
    }
}

// Redis connection for rate limiting
$container->set('redis', function ($container) {
    $logger = $container->get('log.service');
    
    if (class_exists('\\Redis')) {
        $logger->info("Using Redis for rate limiting");
        $redis = new \Redis();
        $host = $_ENV['REDIS_HOST'] ?? 'redis';
        $port = (int)($_ENV['REDIS_PORT'] ?? 6379);
        
        try {
            $redis->connect($host, $port);
            return $redis;
        } catch (\Exception $e) {
            $logger->warning("Failed to connect to Redis, using mock implementation", ['error' => $e->getMessage()]);
            return new MockRedis();
        }
    } else {
        $logger->warning("Redis extension not available, using mock implementation");
        return new MockRedis();
    }
});

// Rate limiting service
$container->set('rate_limit.service', function ($container) {
    return new RateLimitService(
        $container->get('redis'),
        (int)($_ENV['RATE_LIMIT_MAX'] ?? 60),
        (int)($_ENV['RATE_LIMIT_WINDOW'] ?? 60)
    );
});

// Validation service
$container->set('validation.service', function ($container) {
    return new ValidationService();
});

// Auth service
$container->set('auth.service', function ($container) {
    return new AuthService(
        $container->get('database'),
        $_ENV['JWT_SECRET'] ?? 'recipe-api-secure-secret-key',
        $container->get('log.service'),
        (int)($_ENV['JWT_TTL'] ?? 3600)  // Pass token expiration as the fourth parameter
    );
});

// Auth middleware
$container->set('auth.middleware', function ($container) {
    return new AuthMiddleware(
        $container->get('auth.service'),
        $container->get('log.service')
    );
});

// Repositories
$container->set('recipe.repository', function ($container) {
    return new RecipeRepository($container->get('database'));
});

// Controllers
$container->set('recipe.controller', function ($container) {
    return new RecipeController(
        $container->get('recipe.repository'),
        $container->get('validation.service'),
        $container->get('log.service')
    );
});

$container->set('auth.controller', function ($container) {
    return new AuthController(
        $container->get('auth.service'),
        $container->get('validation.service'),
        $container->get('log.service')
    );
});

$container->set('swagger.controller', function ($container) {
    return new SwaggerController();
});

// UI Controller
$container->set('ui.controller', function ($container) {
    return new UIController();
});

// Router
$container->set('router', function ($container) {
    $router = new Router(
        $container,
        $container->get('auth.middleware')
    );
    
  
    $router->addRoute('GET', '/', 'ui.controller', 'index', false);
    
    // Recipe routes
    $router->addRoute('GET', '/recipes', 'recipe.controller', 'listAll', false);
    $router->addRoute('POST', '/recipes', 'recipe.controller', 'create', true);
    $router->addRoute('GET', '/recipes/{id}', 'recipe.controller', 'getOne', false);
    $router->addRoute('PUT', '/recipes/{id}', 'recipe.controller', 'update', true);
    $router->addRoute('DELETE', '/recipes/{id}', 'recipe.controller', 'delete', true);
    $router->addRoute('POST', '/recipes/{id}/rating', 'recipe.controller', 'rate', false);
    $router->addRoute('GET', '/recipes/search', 'recipe.controller', 'search', false);
    
    // Auth routes
    $router->addRoute('POST', '/auth/login', 'auth.controller', 'login', false);
    $router->addRoute('POST', '/auth/register', 'auth.controller', 'register', false);
    
    // Swagger UI routes
    $router->addRoute('GET', '/swagger', 'swagger.controller', 'showUI', false);
    $router->addRoute('GET', '/swagger.json', 'swagger.controller', 'getJson', false);
    
    // UI routes
    $router->addRoute('GET', '/ui', 'ui.controller', 'index', false);
    $router->addRoute('GET', '/ui/{path}', 'ui.controller', 'serveFile', false);
    
    // API version routes (for future expansion)
    $router->addRoute('GET', '/v1/recipes', 'recipe.controller', 'listAll', false);
    
    return $router;
});
