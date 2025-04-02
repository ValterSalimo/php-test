<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Service\AuthService;

class AuthMiddleware
{
    private $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function process(Request $request)
    {
        $token = $request->getBearerToken();
        
        if (!$token) {
            return new Response(['error' => 'Authentication required'], 401);
        }
        
        $userData = $this->authService->validateToken($token);
        if (!$userData) {
            return new Response(['error' => 'Invalid or expired token'], 401);
        }
        
        // Token is valid, continue
        return true;
    }
}
