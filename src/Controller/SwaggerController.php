<?php

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;

class SwaggerController
{
    public function showUI(Request $request)
    {
        // Skip PHP processing and read the file directly
        ob_start();
        include __DIR__ . '/../../web/swagger.php';
        $content = ob_get_clean();
        
        // Return as HTML with appropriate Content-Type
        return new Response(
            $content,
            200,
            ['Content-Type' => 'text/html']
        );
    }
    
    public function getJson(Request $request)
    {
        // Read the swagger.json file directly as a string
        $swagger = file_get_contents(__DIR__ . '/../../web/swagger.json');
        
        // Return the raw JSON string with proper headers
        return new Response(
            json_decode($swagger, true), 
            200,
            ['Content-Type' => 'application/json']
        );
    }
}
