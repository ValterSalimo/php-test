<?php

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\AuthService;

class AuthController
{
    private $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request)
    {
        $data = $request->getBody();
        
        if (!isset($data['username']) || !isset($data['password'])) {
            return new Response(['error' => 'Username and password are required'], 400);
        }
        
        try {
            $token = $this->authService->authenticate($data['username'], $data['password']);
            return new Response(['token' => $token]);
        } catch (\Exception $e) {
            return new Response(['error' => $e->getMessage()], 401);
        }
    }

    public function register(Request $request)
    {
        $data = $request->getBody();
        
        if (!isset($data['username']) || !isset($data['password'])) {
            return new Response(['error' => 'Username and password are required'], 400);
        }
        
        try {
            $this->authService->register($data['username'], $data['password']);
            return new Response(['message' => 'User registered successfully'], 201);
        } catch (\Exception $e) {
            return new Response(['error' => $e->getMessage()], 400);
        }
    }
}
