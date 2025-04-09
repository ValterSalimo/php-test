<?php

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;

class SwaggerController
{
    public function showUI(Request $request)
    {
        // Use a relative path that's more likely to exist
        $content = file_get_contents(__DIR__ . '/../../web/swagger/index.html');
        
        if (!$content) {
            // Fallback to serving embedded Swagger UI
            $content = $this->getEmbeddedSwaggerUI();
        }
        
        return new Response($content, 200, ['Content-Type' => 'text/html']);
    }
    
    public function getJson(Request $request)
    {
        // Serve the OpenAPI specification JSON
        $content = file_get_contents(__DIR__ . '/../../web/swagger.json');
        
        if (!$content) {
            // Return a basic swagger definition if the file doesn't exist
            $content = json_encode($this->getBasicSwaggerDefinition());
        }
        
        return new Response($content, 200, ['Content-Type' => 'application/json']);
    }
    
    public function redirectToSwagger(Request $request)
    {
        // Redirect the root URL to /swagger
        $host = $request->getHostWithPort();
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $redirect = $protocol . '://' . $host . '/swagger';
        
        return new Response('', 302, ['Location' => $redirect]);
    }
    
    /**
     * Returns an embedded Swagger UI HTML when the file is not found
     */
    private function getEmbeddedSwaggerUI()
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recipe API - Swagger UI</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@3/swagger-ui.css">
    <style>
        html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin: 0; padding: 0; }
        .topbar { display: none; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@3/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@3/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: "/swagger.json",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "BaseLayout",
                persistAuthorization: true
            });
            window.ui = ui;
        };
    </script>
</body>
</html>
HTML;
    }
    
    /**
     * Returns a basic Swagger definition when the file is not found
     */
    private function getBasicSwaggerDefinition()
    {
        return [
            "openapi" => "3.0.0",
            "info" => [
                "title" => "Recipe API",
                "description" => "API for managing recipes",
                "version" => "1.0.0"
            ],
            "servers" => [
                ["url" => "http://localhost:8080"]
            ],
            "paths" => [
                "/recipes" => [
                    "get" => [
                        "summary" => "Get all recipes",
                        "responses" => [
                            "200" => [
                                "description" => "Successful operation"
                            ]
                        ]
                    ],
                    "post" => [
                        "summary" => "Create a new recipe",
                        "security" => [["bearerAuth" => []]],
                        "responses" => [
                            "201" => [
                                "description" => "Recipe created"
                            ],
                            "401" => [
                                "description" => "Unauthorized"
                            ]
                        ]
                    ]
                ],
                "/auth/login" => [
                    "post" => [
                        "summary" => "Login to get JWT token",
                        "requestBody" => [
                            "required" => true,
                            "content" => [
                                "application/json" => [
                                    "schema" => [
                                        "type" => "object",
                                        "properties" => [
                                            "username" => ["type" => "string"],
                                            "password" => ["type" => "string"]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        "responses" => [
                            "200" => [
                                "description" => "Successful login"
                            ],
                            "401" => [
                                "description" => "Invalid credentials"
                            ]
                        ]
                    ]
                ]
            ],
            "components" => [
                "securitySchemes" => [
                    "bearerAuth" => [
                        "type" => "http",
                        "scheme" => "bearer",
                        "bearerFormat" => "JWT"
                    ]
                ]
            ]
        ];
    }
}
