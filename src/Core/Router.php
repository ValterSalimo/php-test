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
        
        // If root path, return welcome message
        if ($path === '/' || $path === '') {
            return new Response([
                'message' => 'Welcome to the Recipe API',
                'version' => '1.0'
            ]);
        }
        
        foreach ($this->routes as $route) {
            if ($this->matchRoute($route['path'], $path) && $route['method'] === $method) {
                // Check if route is protected
                if ($route['protected']) {
                    $authResult = $this->authMiddleware->process($request);
                    if ($authResult instanceof Response) {
                        return $authResult;
                    }
                }
                
                // Dispatch to controller
                $controller = $this->container->get($route['controller']);
                $action = $route['action'];
                
                return $controller->$action($request);
            }
        }
        
        return new Response(['error' => 'Not Found', 'path' => $path, 'method' => $method], 404);
    }

    private function matchRoute($routePath, $requestPath)
    {
        // Simple route matching (can be enhanced for more complex patterns)
        $routeRegex = preg_replace('/\{id\}/', '(\d+)', str_replace('/', '\/', $routePath));
        return preg_match('/^' . $routeRegex . '$/', $requestPath);
    }
}
