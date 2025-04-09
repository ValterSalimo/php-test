<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Service\RateLimitService;

class RateLimitMiddleware
{
    private $rateLimitService;
    
    public function __construct(RateLimitService $rateLimitService)
    {
        $this->rateLimitService = $rateLimitService;
    }
    
    public function process(Request $request, callable $next)
    {
        $identifier = $this->getIdentifier($request);
        
        $result = $this->rateLimitService->check($identifier);
        
        if (!$result['allowed']) {
            $retryAfter = $result['retry_after'] ?? 60;
            
            return new Response([
                'error' => 'Rate limit exceeded',
                'message' => 'Too many requests, please try again later'
            ], 429, [
                'X-RateLimit-Limit' => $result['limit'],
                'X-RateLimit-Remaining' => $result['remaining'],
                'X-RateLimit-Reset' => $result['reset'],
                'Retry-After' => $retryAfter
            ]);
        }
        
        $response = $next($request);
        
        if ($response instanceof Response) {
            $response->setHeader('X-RateLimit-Limit', $result['limit']);
            $response->setHeader('X-RateLimit-Remaining', $result['remaining']);
            $response->setHeader('X-RateLimit-Reset', $result['reset']);
        }
        
        return $response;
    }
    
    private function getIdentifier(Request $request): string
    {
        // Use IP address as identifier by default
        $identifier = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        // If authenticated, use user ID
        $userId = $request->getAttribute('user_id');
        if ($userId) {
            $identifier = 'user_' . $userId;
        }
        
        // Add the request path to make rate limits per-endpoint
        $path = $request->getPath();
        $identifier .= ':' . $path;
        
        return $identifier;
    }
}
