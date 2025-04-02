<?php

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;

class UIController
{
    public function index(Request $request)
    {
        // Serve the UI index.html file
        $content = file_get_contents(__DIR__ . '/../../web/ui/index.html');
        return new Response($content, 200, ['Content-Type' => 'text/html']);
    }
    
    public function serveFile(Request $request)
    {
        $path = $request->getParam('path', '');
        $filePath = __DIR__ . '/../../web/ui/' . $path;
        
        if (!file_exists($filePath)) {
            return new Response(['error' => 'File not found'], 404);
        }
        
        $content = file_get_contents($filePath);
        
        // Set the correct content type based on file extension
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $contentType = $this->getContentType($extension);
        
        return new Response($content, 200, ['Content-Type' => $contentType]);
    }
    
    private function getContentType($extension)
    {
        $contentTypes = [
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml'
        ];
        
        return $contentTypes[$extension] ?? 'text/plain';
    }
}
