<?php

namespace App\Core;

use App\Core\Request;
use App\Core\Response;
use App\Middleware\AuthMiddleware;

class Router
{
    private $routes = [];
    private $container;
    private $authMiddleware;
    private $routeParams = [];

    public function __construct($container, $authMiddleware)
    {
        $this->container = $container;
        $this->authMiddleware = $authMiddleware;
    }

    public function addRoute(string $method, string $path, string $controller, string $action, bool $protected = false)
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'controller' => $controller,
            'action' => $action,
            'protected' => $protected
        ];
    }

    public function dispatch(Request $request)
    {
        $path = $request->getPath();
        $method = $request->getMethod();
        
        // Strip query parameters from path if they got included somehow
        if (strpos($path, '?') !== false) {
            $path = strstr($path, '?', true);
        }
        
        // Remove trailing slashes for consistent matching
        $path = rtrim($path, '/');
        
        // Special case for search route - log more details for debugging
        if (substr($path, -14) === '/recipes/search') {
            error_log("Search route matched: $path with method $method");
            error_log("Full request URI: " . $_SERVER['REQUEST_URI']);
            error_log("Query params: " . json_encode($request->getParams()));
        }
        
        // Special handling for the root path
        if ($path === '' || $path === '/') {
            $host = $request->getHostWithPort();
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $redirectUrl = $protocol . '://' . $host . '/swagger';
            
            return new Response('', 302, ['Location' => $redirectUrl]);
        }
        
        // Check routes with a more robust matching system
        foreach ($this->routes as $route) {
            $pathMatched = $this->matchRoute($route['path'], $path);
            $methodMatched = $route['method'] === $method;
            
            error_log("Checking route {$route['path']} against $path: " . ($pathMatched ? 'matched' : 'not matched'));
            
            if ($pathMatched && $methodMatched) {
                if ($route['protected']) {
                    $authResult = $this->authMiddleware->process($request);
                    if ($authResult instanceof Response) {
                        return $authResult;
                    }
                }
                
                try {
                    $controller = $this->container->get($route['controller']);
                    $action = $route['action'];
                    
                    // Add debug information
                    error_log("Executing controller: {$route['controller']}::$action");
                    
                    if (!empty($this->routeParams)) {
                        foreach ($this->routeParams as $key => $value) {
                            if (method_exists($request, 'setParam')) {
                                $request->setParam($key, $value);
                            } else {
                                $params = $request->getParams();
                                $params[$key] = $value;
                            }
                        }
                    }
                    
                    return $controller->$action($request);
                } catch (\Exception $e) {
                    error_log("Error in controller action {$route['action']}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                    return new Response(['error' => 'Internal server error: ' . $e->getMessage()], 500);
                }
            }
        }
        
        // No route matched
        error_log("No route matched for: $path ($method)");
        return new Response(
            ['error' => 'Not Found', 'path' => $path, 'method' => $method],
            404
        );
    }

    private function matchRoute($routePath, $requestPath)
    {
        $this->routeParams = [];
        
        
        if ($routePath === $requestPath) {
            error_log("Exact match for route: $routePath");
            return true;
        }
        
        
        if ($routePath === '/recipes/search' && $requestPath === '/recipes/search') {
            error_log("Special case match for search route");
            return true;
        }
        
        
        if ($routePath === '/recipes/{id}' && $requestPath === '/recipes/search') {
            error_log("Prevented /recipes/search from matching /recipes/{id} pattern");
            return false;
        }
        
   
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?<$1>[^/]+)', $routePath);
        $pattern = str_replace('/', '\\/', $pattern);
        $pattern = '/^' . $pattern . '$/';
        
        if (preg_match($pattern, $requestPath, $matches)) {
            foreach ($matches as $key => $value) {
                if (!is_numeric($key)) {
                  
                    if ($key === 'id' && !is_numeric($value)) {
                        error_log("Invalid ID parameter: $value is not numeric");
                        return false;
                    }
                    
                    $this->routeParams[$key] = $value;
                }
            }
            
            error_log("Pattern match for route: $routePath with params: " . json_encode($this->routeParams));
            return true;
        }
        
        return false;
    }
    
    public function getRouteParams()
    {
        return $this->routeParams;
    }
}
