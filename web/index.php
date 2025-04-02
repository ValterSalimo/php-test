<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Core\Request;
use App\Core\Response;
use App\Core\Container;
use App\Exception\ApiException;
use App\Exception\ValidationException;
use App\Exception\AuthenticationException;
use App\Exception\NotFoundException;

// Load environment variables conditionally
if (class_exists('\\Dotenv\\Dotenv')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
} else {
    // Fallback for when .env file isn't available
    $_ENV = array_merge($_ENV, [
        'DB_HOST' => 'postgres',
        'DB_PORT' => '5432',
        'DB_NAME' => 'hellofresh',
        'DB_USER' => 'postgres',
        'DB_PASSWORD' => 'valter123',
        'JWT_SECRET' => 'recipe-api-secure-secret-key',
        'APP_DEBUG' => '1'
    ]);
}

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', $_ENV['APP_DEBUG'] ?? '0');
ini_set('log_errors', '1');
ini_set('error_log', '/server/http/logs/php-error.log');

// Add CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept-Version");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Initialize DI container
$container = new Container();
require_once __DIR__ . '/../config/services.php';

// Create router instance
$router = $container->get('router');
$request = Request::createFromGlobals();
$logger = $container->get('log.service');

// Special handling for Swagger UI and UI routes
if (strpos($request->getPath(), '/swagger') === 0 || strpos($request->getPath(), '/ui') === 0) {
    // Don't set default content type for these routes
} else {
    // Set content type for API responses
    header("Content-Type: application/json");
}

// API Version control
$apiVersion = $request->getHeader('Accept-Version') ?? 'v1';
if ($apiVersion !== 'v1') {
    $response = new Response([
        'error' => 'API version not supported. Please use Accept-Version: v1'
    ], 400);
    $response->send();
    exit;
}

// Check rate limiting for auth endpoints
if (strpos($request->getPath(), '/auth/') === 0) {
    try {
        $rateLimiter = $container->get('rate_limit.service');
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        if ($rateLimiter->isRateLimited($clientIp)) {
            // Set rate limit headers
            $resetTime = $rateLimiter->getResetTime($clientIp);
            header("X-RateLimit-Limit: 60");
            header("X-RateLimit-Remaining: 0");
            header("X-RateLimit-Reset: $resetTime");
            header("Retry-After: $resetTime");
            
            $response = new Response([
                'error' => 'Rate limit exceeded. Try again later.'
            ], 429);
            $response->send();
            exit;
        }
    } catch (\Exception $e) {
        // If rate limiting fails, just log and continue
        $logger->error("Rate limiting error: " . $e->getMessage());
    }
}

// Log the incoming request
$logger->info('Incoming request', [
    'method' => $request->getMethod(),
    'path' => $request->getPath(),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);

// Handle the request
try {
    // Add a welcome message for the root URL
    if ($request->getPath() == '/' || $request->getPath() == '') {
        // Redirect to UI
        header('Location: /ui');
        exit;
    } else {
        $response = $router->dispatch($request);
    }
    
    // Log the response
    $logger->info('Response', [
        'path' => $request->getPath(),
        'status' => $response->getStatusCode()
    ]);
    
    $response->send();
} catch (ValidationException $e) {
    $logger->warning('Validation error', [
        'path' => $request->getPath(),
        'message' => $e->getMessage(),
        'details' => $e->getDetails()
    ]);
    
    $response = new Response([
        'error' => $e->getMessage(),
        'details' => $e->getDetails()
    ], $e->getCode());
    $response->send();
} catch (AuthenticationException $e) {
    $logger->warning('Authentication error', [
        'path' => $request->getPath(),
        'message' => $e->getMessage()
    ]);
    
    $response = new Response(['error' => $e->getMessage()], $e->getCode());
    $response->send();
} catch (NotFoundException $e) {
    $logger->info('Resource not found', [
        'path' => $request->getPath(),
        'message' => $e->getMessage()
    ]);
    
    $response = new Response(['error' => $e->getMessage()], $e->getCode());
    $response->send();
} catch (ApiException $e) {
    $logger->error('API error', [
        'path' => $request->getPath(),
        'message' => $e->getMessage(),
        'details' => $e->getDetails()
    ]);
    
    $response = new Response([
        'error' => $e->getMessage(),
        'details' => $e->getDetails()
    ], $e->getCode());
    $response->send();
} catch (\Exception $e) {
    $logger->critical('Unhandled exception', [
        'path' => $request->getPath(),
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    $response = new Response(['error' => 'Internal server error'], 500);
    $response->send();
}
