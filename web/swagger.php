<?php
// Disable JSON content type for this file
header("Content-Type: text/html");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recipe API - Swagger UI</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@3/swagger-ui.css">
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        
        *,
        *:before,
        *:after {
            box-sizing: inherit;
        }
        
        body {
            margin: 0;
            background: #fafafa;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@3/swagger-ui-bundle.js"></script>
    <script>
        window.onload = function() {
            SwaggerUIBundle({
                url: "/swagger.json",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.SwaggerUIStandalonePreset
                ],
                layout: "BaseLayout",
                requestInterceptor: (request) => {
                    // Add Authorization header with bearer token if available
                    const token = localStorage.getItem('token');
                    if (token) {
                        request.headers.Authorization = `Bearer ${token}`;
                    }
                    return request;
                },
                responseInterceptor: (response) => {
                    // Capture token from login response
                    if (response.url.includes('/auth/login') && response.status === 200) {
                        try {
                            const data = JSON.parse(response.text);
                            if (data.token) {
                                localStorage.setItem('token', data.token);
                                console.log('Token saved');
                            }
                        } catch (e) {
                            console.error('Failed to parse token', e);
                        }
                    }
                    return response;
                }
            });
        }
    </script>
</body>
</html>
