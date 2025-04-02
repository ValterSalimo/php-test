<?php

namespace App\Core;

class Request
{
    private $path;
    private $method;
    private $params;
    private $body;
    private $headers;

    public function __construct(string $path, string $method, array $params = [], $body = null, array $headers = [])
    {
        $this->path = $path;
        $this->method = strtoupper($method);
        $this->params = $params;
        $this->body = $body;
        $this->headers = $headers;
    }

    public static function createFromGlobals()
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];
        $params = array_merge($_GET, $_POST);
        
        // Parse path parameters (e.g., /recipes/{id})
        $pathParams = [];
        if (preg_match('/\/recipes\/(\d+)(?:\/rating)?$/', $path, $matches)) {
            $pathParams['id'] = $matches[1];
        }
        
        $params = array_merge($params, $pathParams);
        
        // Get request body
        $body = json_decode(file_get_contents('php://input'), true);
        
        // Get headers
        $headers = getallheaders();
        
        return new self($path, $method, $params, $body, $headers);
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function getParam($name, $default = null)
    {
        return $this->params[$name] ?? $default;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getHeader($name, $default = null)
    {
        return $this->headers[$name] ?? $default;
    }

    public function getBearerToken()
    {
        $authHeader = $this->getHeader('Authorization');
        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
