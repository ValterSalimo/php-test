<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class CorsMiddleware
{
    private $allowedOrigins;
    private $allowedMethods;
    private $allowedHeaders;
    
    public function __construct(array $allowedOrigins = ['*'], array $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE'], array $allowedHeaders = ['Content-Type', 'Authorization'])
    {
        $this->allowedOrigins = $allowedOrigins;
        $this->allowedMethods = $allowedMethods;
        $this->allowedHeaders = $allowedHeaders;
    }
    
    public function process(Request $request, callable $next)
    {
        $origin = $request->getHeader('Origin');
        
        if ($origin && (in_array('*', $this->allowedOrigins) || in_array($origin, $this->allowedOrigins))) {
            $response = $next($request);
            
            $headers = [
                'Access-Control-Allow-Origin' => $origin,
                'Access-Control-Allow-Methods' => implode(', ', $this->allowedMethods),
                'Access-Control-Allow-Headers' => implode(', ', $this->allowedHeaders),
                'Access-Control-Allow-Credentials' => 'true'
            ];
            
            foreach ($headers as $key => $value) {
                $response->setHeader($key, $value);
            }
            
            return $response;
        }
        
        if ($request->getMethod() === 'OPTIONS') {
            $response = new Response(null, 204);
            
            $headers = [
                'Access-Control-Allow-Origin' => $origin,
                'Access-Control-Allow-Methods' => implode(', ', $this->allowedMethods),
                'Access-Control-Allow-Headers' => implode(', ', $this->allowedHeaders),
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Max-Age' => '86400'
            ];
            
            foreach ($headers as $key => $value) {
                $response->setHeader($key, $value);
            }
            
            return $response;
        }
        
        return $next($request);
    }
}
