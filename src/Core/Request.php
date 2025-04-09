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
        
        $queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
        $params = [];
        
        if ($queryString) {
            error_log("Raw query string: " . $queryString);
            
            $queryString = rtrim($queryString, '&');
            
            parse_str($queryString, $queryParams);
            
            error_log("Parsed params: " . json_encode($queryParams));
            
            $params = $queryParams;
        }
        
        $params = array_merge($params, $_POST);
        
        $pathParams = [];
        
        if ($path !== '/recipes/search') {
            if (preg_match('/\/recipes\/(\d+)(?:\/rating)?$/', $path, $matches)) {
                $pathParams['id'] = (int)$matches[1];
                error_log("Extracted recipe ID: " . $pathParams['id']);
            }
        } else {
            error_log("Processing search route with params: " . json_encode($params));
        }
        
        $params = array_merge($params, $pathParams);
        
        $body = json_decode(file_get_contents('php://input'), true);
        
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

    public function setParam(string $name, $value): void
    {
        $this->params[$name] = $value;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        $name = strtoupper(str_replace('-', '_', $name));
        $serverKey = 'HTTP_' . $name;
        
        if (isset($_SERVER[$serverKey])) {
            return $_SERVER[$serverKey];
        }
        
        if ($name === 'CONTENT_TYPE' || $name === 'CONTENT_LENGTH') {
            $key = $name;
            if (isset($_SERVER[$key])) {
                return $_SERVER[$key];
            }
        }
        
        return null;
    }

    public function acceptsJson(): bool
    {
        $accept = $this->getHeader('Accept') ?? '';
        return strpos($accept, 'application/json') !== false 
            || strpos($accept, '*/*') !== false;
    }

    public function getHostWithPort(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        if (strpos($host, ':') === false) {
            $port = $_SERVER['SERVER_PORT'] ?? null;
            if ($port && $port != '80' && $port != '443') {
                $host .= ':' . $port;
            }
        }
        
        return $host;
    }

    public function getBearerToken(): ?string
    {
        $authHeader = $this->getHeader('Authorization');
        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
